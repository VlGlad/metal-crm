<?php

namespace App\Controller;

use App\Entity\DocumentApprovalStep;
use App\Entity\DocumentWorkflow;
use App\Entity\DocumentWorkflowEvent;
use App\Entity\DocumentWorkflowFile;
use App\Entity\TaskAssignment;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\DocumentWorkflowFileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/document-workflows')]
final class DocumentWorkflowController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $users,
        private readonly DocumentWorkflowFileStorage $storage,
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $workflows = $this->entityManager->getRepository(DocumentWorkflow::class)->findBy([], ['id' => 'DESC']);
        return $this->json([
            'workflows' => array_map(fn (DocumentWorkflow $workflow) => $this->serializeWorkflow($workflow), $workflows),
            'users' => $this->userOptions(),
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        if ($error = $this->validatePayload($data)) return $this->json(['message' => $error], 422);

        $workflow = (new DocumentWorkflow())
            ->setTitle((string) $data['title'])
            ->setDocumentType((string) ($data['documentType'] ?? 'common'))
            ->setDescription($data['description'] ?? null)
            ->setCreatedBy($this->currentUser());
        $this->fillApprovers($workflow, $data['approverIds'] ?? []);
        $this->addEvent($workflow, 'created', 'Документ создан.');
        $this->entityManager->persist($workflow);
        $this->entityManager->flush();

        return $this->json($this->serializeWorkflow($workflow), 201);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $workflow = $this->findWorkflow($id);
        if (!$workflow) return $this->json(['message' => 'Документ не найден.'], 404);
        if ($workflow->getStatus() !== DocumentWorkflow::STATUS_DRAFT) return $this->json(['message' => 'Редактировать можно только черновик.'], 422);
        $data = $this->decodeJson($request);
        if ($error = $this->validatePayload($data)) return $this->json(['message' => $error], 422);

        $workflow
            ->setTitle((string) $data['title'])
            ->setDocumentType((string) ($data['documentType'] ?? 'common'))
            ->setDescription($data['description'] ?? null)
            ->touch();
        $workflow->clearApprovalSteps();
        $this->fillApprovers($workflow, $data['approverIds'] ?? []);
        $this->addEvent($workflow, 'updated', 'Документ обновлён.');
        $this->entityManager->flush();

        return $this->json($this->serializeWorkflow($workflow));
    }

    #[Route('/{id}/files', methods: ['POST'])]
    public function upload(int $id, Request $request): JsonResponse
    {
        $workflow = $this->findWorkflow($id);
        if (!$workflow) return $this->json(['message' => 'Документ не найден.'], 404);
        $files = $request->files->all('files');
        if (!$files) return $this->json(['message' => 'Выберите хотя бы один файл.'], 422);
        $storedNames = [];
        try {
            foreach ($files as $uploadedFile) {
                if (!$uploadedFile instanceof UploadedFile) throw new \RuntimeException('Передан некорректный файл.');
                $stored = $this->storage->store($uploadedFile, (int) $workflow->getId());
                $storedNames[] = $stored['storedName'];
                $file = (new DocumentWorkflowFile())
                    ->setOriginalName($stored['originalName'])
                    ->setStoredName($stored['storedName'])
                    ->setMimeType($stored['mimeType'])
                    ->setSize($stored['size'])
                    ->setUploadedBy($this->currentUser());
                $workflow->addFile($file);
                $this->entityManager->persist($file);
            }
            $workflow->touch();
            $this->addEvent($workflow, 'file_uploaded', 'Загружены файлы.');
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            foreach ($storedNames as $storedName) $this->storage->delete($storedName);
            return $this->json(['message' => $exception instanceof \RuntimeException ? $exception->getMessage() : 'Не удалось сохранить файлы.'], 422);
        }
        return $this->json($this->serializeWorkflow($workflow), 201);
    }

    #[Route('/{workflowId}/files/{fileId}/download', methods: ['GET'])]
    public function download(int $workflowId, int $fileId): JsonResponse|BinaryFileResponse
    {
        $workflow = $this->findWorkflow($workflowId);
        $file = $workflow ? $this->findFile($workflow, $fileId) : null;
        if (!$workflow || !$file) return $this->json(['message' => 'Файл не найден.'], 404);
        $path = $this->storage->path($file->getStoredName());
        if (!is_file($path)) return $this->json(['message' => 'Файл отсутствует в хранилище.'], 404);
        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $file->getMimeType());
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file->getOriginalName(), 'document');
        return $response;
    }

    #[Route('/{workflowId}/files/{fileId}', methods: ['DELETE'])]
    public function deleteFile(int $workflowId, int $fileId): JsonResponse
    {
        $workflow = $this->findWorkflow($workflowId);
        $file = $workflow ? $this->findFile($workflow, $fileId) : null;
        if (!$workflow || !$file) return $this->json(['message' => 'Файл не найден.'], 404);
        $storedName = $file->getStoredName();
        $workflow->removeFile($file)->touch();
        $this->entityManager->remove($file);
        $this->addEvent($workflow, 'file_deleted', 'Файл удалён.');
        $this->entityManager->flush();
        $this->storage->delete($storedName);
        return $this->json($this->serializeWorkflow($workflow));
    }

    #[Route('/{id}/start', methods: ['POST'])]
    public function start(int $id): JsonResponse
    {
        $workflow = $this->findWorkflow($id);
        if (!$workflow) return $this->json(['message' => 'Документ не найден.'], 404);
        if (!$workflow->getFiles()->count()) return $this->json(['message' => 'Перед запуском загрузите хотя бы один файл.'], 422);
        if (!$workflow->getApprovalSteps()->count()) return $this->json(['message' => 'Укажите согласующих.'], 422);
        $workflow->setStatus(DocumentWorkflow::STATUS_IN_APPROVAL)->setStartedAt(new \DateTimeImmutable())->touch();
        $this->addEvent($workflow, 'started', 'Документ отправлен на согласование.');
        $this->entityManager->flush();
        return $this->json($this->serializeWorkflow($workflow));
    }

    #[Route('/{id}/approve', methods: ['POST'])]
    public function approve(int $id, Request $request): JsonResponse { return $this->decide($id, DocumentApprovalStep::STATUS_APPROVED, $request); }
    #[Route('/{id}/reject', methods: ['POST'])]
    public function reject(int $id, Request $request): JsonResponse { return $this->decide($id, DocumentApprovalStep::STATUS_REJECTED, $request); }
    #[Route('/{id}/remarks', methods: ['POST'])]
    public function remarks(int $id, Request $request): JsonResponse { return $this->decide($id, DocumentApprovalStep::STATUS_REMARKS, $request); }

    #[Route('/{id}/assignments', methods: ['POST'])]
    public function createAssignment(int $id, Request $request): JsonResponse
    {
        $workflow = $this->findWorkflow($id);
        if (!$workflow) return $this->json(['message' => 'Документ не найден.'], 404);
        $data = $this->decodeJson($request);
        $title = trim((string) ($data['title'] ?? ''));
        $responsible = $this->users->find((int) ($data['responsibleId'] ?? 0));
        if ($title === '') return $this->json(['message' => 'Укажите поручение.'], 422);
        if (!$responsible) return $this->json(['message' => 'Укажите ответственного.'], 422);
        try { $dueDate = new \DateTimeImmutable((string) ($data['dueDate'] ?? '')); } catch (\Throwable) { return $this->json(['message' => 'Укажите корректный срок.'], 422); }
        $assignment = (new TaskAssignment())
            ->setTitle($title)
            ->setDescription($data['description'] ?? null)
            ->setResponsible($responsible)
            ->setCreatedBy($this->currentUser())
            ->setDocumentWorkflow($workflow)
            ->setDueDate($dueDate);
        $assignment->addEvent((new \App\Entity\TaskAssignmentEvent())->setEventType('created')->setComment('Поручение создано.')->setCreatedBy($this->currentUser()));
        $this->entityManager->persist($assignment);
        $this->addEvent($workflow, 'assignment_created', 'Создано поручение: '.$title);
        $this->entityManager->flush();
        return $this->json(['assignmentId' => $assignment->getId(), 'workflow' => $this->serializeWorkflow($workflow)], 201);
    }

    private function decide(int $id, string $status, Request $request): JsonResponse
    {
        $workflow = $this->findWorkflow($id);
        if (!$workflow) return $this->json(['message' => 'Документ не найден.'], 404);
        if ($workflow->getStatus() !== DocumentWorkflow::STATUS_IN_APPROVAL) return $this->json(['message' => 'Документ не находится на согласовании.'], 422);
        $user = $this->currentUser();
        $step = $this->findUserStep($workflow, $user);
        if (!$step) return $this->json(['message' => 'Вы не назначены согласующим по этому документу.'], 403);
        $comment = $this->decodeJson($request)['comment'] ?? null;
        $step->decide($status, $comment);
        $this->recalculateStatus($workflow);
        $this->addEvent($workflow, 'approval_'.$status, $comment ?: $this->approvalEventText($status));
        $workflow->touch();
        $this->entityManager->flush();
        return $this->json($this->serializeWorkflow($workflow));
    }

    private function recalculateStatus(DocumentWorkflow $workflow): void
    {
        $statuses = array_map(fn (DocumentApprovalStep $step) => $step->getStatus(), $workflow->getApprovalSteps()->toArray());
        if (in_array(DocumentApprovalStep::STATUS_REJECTED, $statuses, true)) {
            $workflow->setStatus(DocumentWorkflow::STATUS_REJECTED)->setCompletedAt(new \DateTimeImmutable());
            return;
        }
        if (in_array(DocumentApprovalStep::STATUS_REMARKS, $statuses, true)) {
            $workflow->setStatus(DocumentWorkflow::STATUS_REMARKS)->setCompletedAt(new \DateTimeImmutable());
            return;
        }
        if ($statuses && !in_array(DocumentApprovalStep::STATUS_PENDING, $statuses, true)) {
            $workflow->setStatus(DocumentWorkflow::STATUS_APPROVED)->setCompletedAt(new \DateTimeImmutable());
        }
    }

    private function validatePayload(array $data): ?string
    {
        if (trim((string) ($data['title'] ?? '')) === '') return 'Укажите название документа.';
        if (!is_array($data['approverIds'] ?? null) || !count($data['approverIds'])) return 'Выберите согласующих.';
        return null;
    }

    private function fillApprovers(DocumentWorkflow $workflow, array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn (int $id) => $id > 0)));
        foreach ($ids as $index => $id) {
            $user = $this->users->find($id);
            if ($user) $workflow->addApprovalStep((new DocumentApprovalStep())->setApprover($user)->setSortOrder($index));
        }
    }

    private function serializeWorkflow(DocumentWorkflow $workflow): array
    {
        return [
            'id' => $workflow->getId(),
            'title' => $workflow->getTitle(),
            'documentType' => $workflow->getDocumentType(),
            'description' => $workflow->getDescription(),
            'status' => $workflow->getStatus(),
            'files' => array_map(fn (DocumentWorkflowFile $file) => [
                'id' => $file->getId(), 'name' => $file->getOriginalName(), 'mimeType' => $file->getMimeType(), 'size' => $file->getSize(),
                'uploadedBy' => $file->getUploadedBy()?->getFullName(), 'uploadedAt' => $file->getUploadedAt()->format(DATE_ATOM),
                'downloadUrl' => sprintf('/document-workflows/%d/files/%d/download', $workflow->getId(), $file->getId()),
            ], $workflow->getFiles()->toArray()),
            'approvals' => array_map(fn (DocumentApprovalStep $step) => [
                'id' => $step->getId(), 'approverId' => $step->getApprover()?->getId(), 'approverName' => $step->getApprover()?->getFullName(),
                'status' => $step->getStatus(), 'comment' => $step->getComment(), 'decidedAt' => $step->getDecidedAt()?->format(DATE_ATOM),
            ], $workflow->getApprovalSteps()->toArray()),
            'events' => array_map(fn (DocumentWorkflowEvent $event) => [
                'id' => $event->getId(), 'eventType' => $event->getEventType(), 'comment' => $event->getComment(),
                'createdBy' => $event->getCreatedBy()?->getFullName(), 'createdAt' => $event->getCreatedAt()->format(DATE_ATOM),
            ], $workflow->getEvents()->toArray()),
            'createdBy' => $workflow->getCreatedBy()?->getFullName(),
            'startedAt' => $workflow->getStartedAt()?->format(DATE_ATOM), 'completedAt' => $workflow->getCompletedAt()?->format(DATE_ATOM),
            'createdAt' => $workflow->getCreatedAt()->format(DATE_ATOM), 'updatedAt' => $workflow->getUpdatedAt()->format(DATE_ATOM),
            'permissions' => ['canStart' => $workflow->getStatus() === DocumentWorkflow::STATUS_DRAFT, 'canEdit' => $workflow->getStatus() === DocumentWorkflow::STATUS_DRAFT, 'canDecide' => (bool) $this->findUserStep($workflow, $this->currentUser())],
        ];
    }

    private function addEvent(DocumentWorkflow $workflow, string $type, ?string $comment = null): void { $workflow->addEvent((new DocumentWorkflowEvent())->setEventType($type)->setComment($comment)->setCreatedBy($this->currentUser())); }
    private function approvalEventText(string $status): string { return ['approved' => 'Документ согласован.', 'rejected' => 'Документ не согласован.', 'remarks' => 'Внесены замечания.'][$status] ?? 'Решение зафиксировано.'; }
    private function findWorkflow(int $id): ?DocumentWorkflow { $workflow = $this->entityManager->getRepository(DocumentWorkflow::class)->find($id); return $workflow instanceof DocumentWorkflow ? $workflow : null; }
    private function findFile(DocumentWorkflow $workflow, int $fileId): ?DocumentWorkflowFile { foreach ($workflow->getFiles() as $file) if ($file->getId() === $fileId) return $file; return null; }
    private function findUserStep(DocumentWorkflow $workflow, ?User $user): ?DocumentApprovalStep { if (!$user) return null; foreach ($workflow->getApprovalSteps() as $step) if ($step->getApprover()?->getId() === $user->getId()) return $step; return null; }
    private function userOptions(): array { return array_map(fn (User $user) => ['id' => $user->getId(), 'name' => $user->getFullName(), 'email' => $user->getEmail()], $this->users->findBy(['isActive' => true], ['fullName' => 'ASC'])); }
    private function currentUser(): ?User { $user = $this->getUser(); return $user instanceof User ? $user : null; }
    private function decodeJson(Request $request): array { try { $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR); } catch (\Throwable) { return []; } return is_array($data) ? $data : []; }
}