<?php

namespace App\Controller;

use App\Entity\DocumentWorkflow;
use App\Entity\TaskAssignment;
use App\Entity\TaskAssignmentEvent;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/task-assignments')]
final class TaskAssignmentController extends AbstractController
{
    private const LEADERSHIP_ROLES = [
        User::ROLE_ADMIN,
        User::ROLE_GENERAL_DIRECTOR,
        User::ROLE_PRODUCTION_HEAD,
        User::ROLE_DEPARTMENT_HEAD,
        User::ROLE_PTO_HEAD,
        User::ROLE_PO_HEAD,
        User::ROLE_OMTS_HEAD,
        User::ROLE_SALES_HEAD,
        User::ROLE_OTK_HEAD,
        User::ROLE_CRO_HEAD,
        User::ROLE_SSC_HEAD,
        User::ROLE_CPO_HEAD,
        User::ROLE_CZL_HEAD,
        User::ROLE_OSMK_HEAD,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $users,
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        $assignments = $this->visibleAssignments($user);
        $month = $request->query->get('month') ?: (new \DateTimeImmutable())->format('Y-m');
        return $this->json([
            'assignments' => array_map(fn (TaskAssignment $assignment) => $this->serializeAssignment($assignment), $assignments),
            'users' => $this->userOptions(),
            'documents' => $this->documentOptions(),
            'report' => $this->buildReport($assignments, $month),
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $assignmentOrError = $this->assignmentFromPayload(new TaskAssignment(), $data, $this->currentUser());
        if (is_string($assignmentOrError)) return $this->json(['message' => $assignmentOrError], 422);
        $assignmentOrError->setCreatedBy($this->currentUser());
        $this->addEvent($assignmentOrError, 'created', 'Поручение создано.');
        $this->entityManager->persist($assignmentOrError);
        $this->entityManager->flush();
        return $this->json($this->serializeAssignment($assignmentOrError), 201);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $assignment = $this->findAssignment($id);
        if (!$assignment || !$this->canView($assignment, $this->currentUser())) return $this->json(['message' => 'Поручение не найдено.'], 404);
        if (!$this->canManage($assignment, $this->currentUser())) return $this->json(['message' => 'Нет права редактировать это поручение.'], 403);
        if ($assignment->getStatus() === TaskAssignment::STATUS_COMPLETED) return $this->json(['message' => 'Выполненное поручение нельзя редактировать.'], 422);
        $assignmentOrError = $this->assignmentFromPayload($assignment, $this->decodeJson($request), $this->currentUser());
        if (is_string($assignmentOrError)) return $this->json(['message' => $assignmentOrError], 422);
        $assignment->touch();
        $this->addEvent($assignment, 'updated', 'Поручение обновлено.');
        $this->entityManager->flush();
        return $this->json($this->serializeAssignment($assignment));
    }

    #[Route('/{id}/start', methods: ['POST'])]
    public function start(int $id): JsonResponse
    {
        $assignment = $this->findAssignment($id);
        if (!$assignment || !$this->canView($assignment, $this->currentUser())) return $this->json(['message' => 'Поручение не найдено.'], 404);
        if (!$this->canWorkOn($assignment, $this->currentUser())) return $this->json(['message' => 'Взять поручение в работу может ответственный или админ.'], 403);
        $assignment->setStatus(TaskAssignment::STATUS_IN_PROGRESS)->touch();
        $this->addEvent($assignment, 'started', 'Поручение взято в работу.');
        $this->entityManager->flush();
        return $this->json($this->serializeAssignment($assignment));
    }

    #[Route('/{id}/complete', methods: ['POST'])]
    public function complete(int $id, Request $request): JsonResponse
    {
        $assignment = $this->findAssignment($id);
        if (!$assignment || !$this->canView($assignment, $this->currentUser())) return $this->json(['message' => 'Поручение не найдено.'], 404);
        $user = $this->currentUser();
        if (!$this->canWorkOn($assignment, $user)) {
            return $this->json(['message' => 'Выполнить поручение может ответственный или админ.'], 403);
        }
        $comment = $this->decodeJson($request)['comment'] ?? 'Поручение выполнено.';
        $assignment->setStatus(TaskAssignment::STATUS_COMPLETED)->setCompletedAt(new \DateTimeImmutable())->touch();
        $this->addEvent($assignment, 'completed', $comment);
        $this->entityManager->flush();
        return $this->json($this->serializeAssignment($assignment));
    }

    #[Route('/{id}/cancel', methods: ['POST'])]
    public function cancel(int $id, Request $request): JsonResponse
    {
        $assignment = $this->findAssignment($id);
        if (!$assignment || !$this->canView($assignment, $this->currentUser())) return $this->json(['message' => 'Поручение не найдено.'], 404);
        if (!$this->canManage($assignment, $this->currentUser())) return $this->json(['message' => 'Нет права отменять это поручение.'], 403);
        $assignment->setStatus(TaskAssignment::STATUS_CANCELLED)->touch();
        $this->addEvent($assignment, 'cancelled', $this->decodeJson($request)['comment'] ?? 'Поручение отменено.');
        $this->entityManager->flush();
        return $this->json($this->serializeAssignment($assignment));
    }

    #[Route('/report', methods: ['GET'])]
    public function report(Request $request): JsonResponse
    {
        $month = $request->query->get('month') ?: (new \DateTimeImmutable())->format('Y-m');
        $assignments = $this->visibleAssignments($this->currentUser());
        return $this->json($this->buildReport($assignments, $month));
    }

    private function assignmentFromPayload(TaskAssignment $assignment, array $data, ?User $user): TaskAssignment|string
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') return 'Укажите название поручения.';
        $responsible = $this->users->find((int) ($data['responsibleId'] ?? 0));
        if (!$responsible) return 'Укажите ответственного.';
        try { $dueDate = new \DateTimeImmutable((string) ($data['dueDate'] ?? '')); } catch (\Throwable) { return 'Укажите корректный срок.'; }
        $document = null;
        $documentId = (int) ($data['documentWorkflowId'] ?? 0);
        if ($documentId > 0) {
            $document = $this->entityManager->getRepository(DocumentWorkflow::class)->find($documentId);
            if (!$document instanceof DocumentWorkflow || !$this->canViewDocument($document, $user)) {
                return 'Документ-источник не найден или недоступен.';
            }
        }
        return $assignment
            ->setTitle($title)
            ->setDescription($data['description'] ?? null)
            ->setResponsible($responsible)
            ->setDocumentWorkflow($document)
            ->setDueDate($dueDate);
    }

    private function serializeAssignment(TaskAssignment $assignment): array
    {
        return [
            'id' => $assignment->getId(),
            'title' => $assignment->getTitle(),
            'description' => $assignment->getDescription(),
            'status' => $this->effectiveStatus($assignment),
            'rawStatus' => $assignment->getStatus(),
            'responsibleId' => $assignment->getResponsible()?->getId(),
            'responsibleName' => $assignment->getResponsible()?->getFullName(),
            'createdBy' => $assignment->getCreatedBy()?->getFullName(),
            'documentWorkflowId' => $assignment->getDocumentWorkflow()?->getId(),
            'documentWorkflowTitle' => $assignment->getDocumentWorkflow()?->getTitle(),
            'dueDate' => $assignment->getDueDate()->format('Y-m-d'),
            'completedAt' => $assignment->getCompletedAt()?->format(DATE_ATOM),
            'createdAt' => $assignment->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $assignment->getUpdatedAt()->format(DATE_ATOM),
            'events' => array_map(fn (TaskAssignmentEvent $event) => [
                'id' => $event->getId(), 'eventType' => $event->getEventType(), 'comment' => $event->getComment(),
                'createdBy' => $event->getCreatedBy()?->getFullName(), 'createdAt' => $event->getCreatedAt()->format(DATE_ATOM),
            ], $assignment->getEvents()->toArray()),
            'permissions' => [
                'canEdit' => $this->canManage($assignment, $this->currentUser()),
                'canStart' => $this->canWorkOn($assignment, $this->currentUser()),
                'canComplete' => $this->canWorkOn($assignment, $this->currentUser()),
                'canCancel' => $this->canManage($assignment, $this->currentUser()),
            ],
        ];
    }

    private function buildReport(array $assignments, string $month): array
    {
        $start = new \DateTimeImmutable($month.'-01');
        $end = $start->modify('last day of this month')->setTime(23, 59, 59);
        $rows = [];
        $total = ['total' => 0, 'completed' => 0, 'completedInTime' => 0, 'completedLate' => 0, 'notCompleted' => 0, 'overdue' => 0];
        foreach ($assignments as $assignment) {
            if (!$assignment instanceof TaskAssignment) continue;
            if ($assignment->getDueDate() < $start || $assignment->getDueDate() > $end) continue;
            $name = $assignment->getResponsible()?->getFullName() ?: 'Без ответственного';
            $rows[$name] ??= ['responsible' => $name, 'total' => 0, 'completed' => 0, 'completedInTime' => 0, 'completedLate' => 0, 'notCompleted' => 0, 'overdue' => 0, 'completionRate' => 0];
            $status = $this->effectiveStatus($assignment);
            $rows[$name]['total']++; $total['total']++;
            if ($assignment->getStatus() === TaskAssignment::STATUS_COMPLETED) {
                $rows[$name]['completed']++; $total['completed']++;
                if ($assignment->getCompletedAt() && $assignment->getCompletedAt() <= $assignment->getDueDate()->setTime(23,59,59)) { $rows[$name]['completedInTime']++; $total['completedInTime']++; }
                else { $rows[$name]['completedLate']++; $total['completedLate']++; }
            } else {
                $rows[$name]['notCompleted']++; $total['notCompleted']++;
                if ($status === 'overdue') { $rows[$name]['overdue']++; $total['overdue']++; }
            }
        }
        foreach ($rows as &$row) $row['completionRate'] = $row['total'] ? round($row['completed'] / $row['total'] * 100) : 0;
        $total['completionRate'] = $total['total'] ? round($total['completed'] / $total['total'] * 100) : 0;
        return ['month' => $month, 'total' => $total, 'rows' => array_values($rows)];
    }

    private function effectiveStatus(TaskAssignment $assignment): string
    {
        if ($assignment->getStatus() !== TaskAssignment::STATUS_COMPLETED && $assignment->getStatus() !== TaskAssignment::STATUS_CANCELLED && $assignment->getDueDate() < (new \DateTimeImmutable('today'))) return 'overdue';
        return $assignment->getStatus();
    }

    private function visibleAssignments(?User $user): array
    {
        return array_values(array_filter(
            $this->entityManager->getRepository(TaskAssignment::class)->findBy([], ['dueDate' => 'ASC', 'id' => 'DESC']),
            fn (TaskAssignment $assignment) => $this->canView($assignment, $user)
        ));
    }
    private function canView(TaskAssignment $assignment, ?User $user): bool
    {
        if (!$user) return false;
        return $this->hasAnyRole($user, self::LEADERSHIP_ROLES)
            || $assignment->getCreatedBy()?->getId() === $user->getId()
            || $assignment->getResponsible()?->getId() === $user->getId();
    }
    private function canManage(TaskAssignment $assignment, ?User $user): bool
    {
        if (!$user) return false;
        return $this->hasAnyRole($user, self::LEADERSHIP_ROLES) || $assignment->getCreatedBy()?->getId() === $user->getId();
    }
    private function canWorkOn(TaskAssignment $assignment, ?User $user): bool
    {
        if (!$user) return false;
        return $this->isAdmin($user) || $assignment->getResponsible()?->getId() === $user->getId();
    }
    private function canViewDocument(DocumentWorkflow $workflow, ?User $user): bool
    {
        if (!$user) return false;
        if ($this->hasAnyRole($user, self::LEADERSHIP_ROLES) || $workflow->getCreatedBy()?->getId() === $user->getId()) return true;
        foreach ($workflow->getApprovalSteps() as $step) {
            if ($step->getApprover()?->getId() === $user->getId()) return true;
        }
        $assignments = $this->entityManager->getRepository(TaskAssignment::class)->findBy(['documentWorkflow' => $workflow]);
        foreach ($assignments as $assignment) {
            if ($assignment instanceof TaskAssignment && $assignment->getResponsible()?->getId() === $user->getId()) return true;
        }
        return false;
    }
    private function hasAnyRole(User $user, array $roles): bool
    {
        foreach ($roles as $role) if ($user->hasRole($role)) return true;
        return false;
    }
    private function addEvent(TaskAssignment $assignment, string $type, ?string $comment = null): void { $assignment->addEvent((new TaskAssignmentEvent())->setEventType($type)->setComment($comment)->setCreatedBy($this->currentUser())); }
    private function findAssignment(int $id): ?TaskAssignment { $assignment = $this->entityManager->getRepository(TaskAssignment::class)->find($id); return $assignment instanceof TaskAssignment ? $assignment : null; }
    private function userOptions(): array { return array_map(fn (User $user) => ['id' => $user->getId(), 'name' => $user->getFullName(), 'email' => $user->getEmail()], $this->users->findBy(['isActive' => true], ['fullName' => 'ASC'])); }
    private function documentOptions(): array
    {
        $user = $this->currentUser();
        $workflows = array_filter(
            $this->entityManager->getRepository(DocumentWorkflow::class)->findBy([], ['id' => 'DESC']),
            fn (DocumentWorkflow $workflow) => $this->canViewDocument($workflow, $user)
        );
        return array_map(fn (DocumentWorkflow $workflow) => ['id' => $workflow->getId(), 'title' => $workflow->getTitle()], array_values($workflows));
    }
    private function currentUser(): ?User { $user = $this->getUser(); return $user instanceof User ? $user : null; }
    private function isAdmin(?User $user): bool { return (bool) $user?->hasRole(User::ROLE_ADMIN); }
    private function decodeJson(Request $request): array { try { $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR); } catch (\Throwable) { return []; } return is_array($data) ? $data : []; }
}
