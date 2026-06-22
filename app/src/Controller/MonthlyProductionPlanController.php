<?php

namespace App\Controller;

use App\Entity\MonthlyProductionPlan;
use App\Entity\MonthlyProductionPlanFile;
use App\Entity\User;
use App\Enum\DocumentType;
use App\Repository\MonthlyProductionPlanRepository;
use App\Security\MonthlyProductionPlanAccess;
use App\Service\MonthlyProductionPlanFileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/monthly-plans')]
final class MonthlyProductionPlanController extends AbstractController
{
    private const DOCUMENT_TYPES = [
        DocumentType::PRODUCTION_PLAN,
        DocumentType::PRODUCTION_SCHEDULE,
        DocumentType::MATERIAL_REQUEST,
    ];

    public function __construct(
        private readonly MonthlyProductionPlanRepository $plans,
        private readonly EntityManagerInterface $entityManager,
        private readonly MonthlyProductionPlanAccess $access,
        private readonly MonthlyProductionPlanFileStorage $storage,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $user = $this->currentUser();

        if (!$this->access->canViewPlans($user)) {
            return $this->json(['message' => 'Нет доступа к планированию.'], 403);
        }

        return $this->json([
            'plans' => array_map(
                fn (MonthlyProductionPlan $plan) => $this->serializePlan($plan, $user),
                $this->plans->findBy([], ['month' => 'DESC', 'id' => 'DESC'])
            ),
            'canCreate' => $this->access->canCreatePlan($user),
        ]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $plan = $this->plans->find($id);

        if (!$plan) {
            return $this->json(['message' => 'Планирование не найдено.'], 404);
        }

        $user = $this->currentUser();

        if (!$this->access->canViewPlans($user)) {
            return $this->json(['message' => 'Нет доступа к планированию.'], 403);
        }

        return $this->json($this->serializePlan($plan, $user));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->currentUser();

        if (!$this->access->canCreatePlan($user)) {
            return $this->json(['message' => 'Нет права создавать планирование.'], 403);
        }

        $data = $this->decodeJson($request);

        if ($error = $this->validatePayload($data)) {
            return $this->json(['message' => $error], 422);
        }

        $plan = (new MonthlyProductionPlan())
            ->setMonth(new \DateTimeImmutable($data['month'] . '-01'))
            ->setName((string) $data['name'])
            ->setCreatedBy($user);

        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        return $this->json($this->serializePlan($plan, $user), 201);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $plan = $this->plans->find($id);

        if (!$plan) {
            return $this->json(['message' => 'Планирование не найдено.'], 404);
        }

        $user = $this->currentUser();

        if (!$this->access->canEditPlan($user)) {
            return $this->json(['message' => 'Нет права редактировать планирование.'], 403);
        }

        $data = $this->decodeJson($request);

        if ($error = $this->validatePayload($data)) {
            return $this->json(['message' => $error], 422);
        }

        $plan
            ->setMonth(new \DateTimeImmutable($data['month'] . '-01'))
            ->setName((string) $data['name'])
            ->touch();

        $this->entityManager->flush();

        return $this->json($this->serializePlan($plan, $user));
    }

    #[Route('/{id}/documents/{type}', methods: ['POST'])]
    public function upload(int $id, string $type, Request $request): JsonResponse
    {
        $plan = $this->plans->find($id);

        if (!$plan) {
            return $this->json(['message' => 'Планирование не найдено.'], 404);
        }

        $documentType = $this->parseDocumentType($type);

        if (!$documentType) {
            return $this->json(['message' => 'Неизвестный тип документа.'], 404);
        }

        $user = $this->currentUser();

        if (!$this->access->canUploadDocument($user, $documentType)) {
            return $this->json(['message' => 'Нет права загружать документы этого типа.'], 403);
        }

        $files = $request->files->all('files');

        if (!$files) {
            return $this->json(['message' => 'Выберите хотя бы один файл.'], 422);
        }

        $storedNames = [];

        try {
            foreach ($files as $uploadedFile) {
                if (!$uploadedFile instanceof UploadedFile) {
                    throw new \RuntimeException('Передан некорректный файл.');
                }

                $stored = $this->storage->store($uploadedFile, (int) $plan->getId());
                $storedNames[] = $stored['storedName'];

                $file = (new MonthlyProductionPlanFile())
                    ->setDocumentType($documentType)
                    ->setOriginalName($stored['originalName'])
                    ->setStoredName($stored['storedName'])
                    ->setMimeType($stored['mimeType'])
                    ->setSize($stored['size'])
                    ->setUploadedBy($user);

                $plan->addFile($file);
                $this->entityManager->persist($file);
            }

            $plan->touch();
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            foreach ($storedNames as $storedName) {
                $this->storage->delete($storedName);
            }

            return $this->json([
                'message' => $exception instanceof \RuntimeException
                    ? $exception->getMessage()
                    : 'Не удалось сохранить файлы.',
            ], 422);
        }

        return $this->json($this->serializePlan($plan, $user), 201);
    }

    #[Route('/{planId}/documents/{fileId}/download', methods: ['GET'])]
    public function download(int $planId, int $fileId): JsonResponse|BinaryFileResponse
    {
        $plan = $this->plans->find($planId);
        $file = $plan ? $this->findFile($plan, $fileId) : null;

        if (!$plan || !$file) {
            return $this->json(['message' => 'Файл не найден.'], 404);
        }

        if (!$this->access->canViewDocument($this->currentUser(), $file->getDocumentType())) {
            return $this->json(['message' => 'Нет доступа к файлу.'], 403);
        }

        $path = $this->storage->path($file->getStoredName());

        if (!is_file($path)) {
            return $this->json(['message' => 'Файл отсутствует в хранилище.'], 404);
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $file->getMimeType());
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $file->getOriginalName(),
            'document'
        );

        return $response;
    }

    #[Route('/{planId}/documents/{fileId}', methods: ['DELETE'])]
    public function deleteFile(int $planId, int $fileId): JsonResponse
    {
        $plan = $this->plans->find($planId);
        $file = $plan ? $this->findFile($plan, $fileId) : null;

        if (!$plan || !$file) {
            return $this->json(['message' => 'Файл не найден.'], 404);
        }

        if (!$this->access->canUploadDocument($this->currentUser(), $file->getDocumentType())) {
            return $this->json(['message' => 'Нет права удалять этот файл.'], 403);
        }

        $storedName = $file->getStoredName();
        $plan->removeFile($file)->touch();
        $this->entityManager->remove($file);
        $this->entityManager->flush();
        $this->storage->delete($storedName);

        return $this->json($this->serializePlan($plan, $this->currentUser()));
    }

    private function serializePlan(MonthlyProductionPlan $plan, ?User $user): array
    {
        $documents = [];
        $documentPermissions = [];

        foreach (self::DOCUMENT_TYPES as $type) {
            $canView = $this->access->canViewDocument($user, $type);
            $documents[$type->value] = $canView
                ? array_values(array_map(
                    fn (MonthlyProductionPlanFile $file) => $this->serializeFile($plan, $file),
                    array_filter(
                        $plan->getFiles()->toArray(),
                        fn (MonthlyProductionPlanFile $file) => $file->getDocumentType() === $type
                    )
                ))
                : null;

            $documentPermissions[$type->value] = [
                'canView' => $canView,
                'canUpload' => $this->access->canUploadDocument($user, $type),
            ];
        }

        return [
            'id' => $plan->getId(),
            'month' => $plan->getMonth()->format('Y-m'),
            'name' => $plan->getName(),
            'documents' => $documents,
            'permissions' => [
                'canEdit' => $this->access->canEditPlan($user),
                'documents' => $documentPermissions,
            ],
            'createdBy' => $plan->getCreatedBy()?->getFullName(),
            'createdAt' => $plan->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $plan->getUpdatedAt()->format(DATE_ATOM),
        ];
    }

    private function serializeFile(MonthlyProductionPlan $plan, MonthlyProductionPlanFile $file): array
    {
        return [
            'id' => $file->getId(),
            'name' => $file->getOriginalName(),
            'mimeType' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploadedBy' => $file->getUploadedBy()?->getFullName(),
            'uploadedAt' => $file->getUploadedAt()->format(DATE_ATOM),
            'downloadUrl' => sprintf(
                '/monthly-plans/%d/documents/%d/download',
                $plan->getId(),
                $file->getId()
            ),
        ];
    }

    private function validatePayload(array $data): ?string
    {
        $month = (string) ($data['month'] ?? '');
        $name = trim((string) ($data['name'] ?? ''));

        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return 'Укажите корректный месяц.';
        }

        if ($name === '') {
            return 'Укажите название планирования.';
        }

        if (mb_strlen($name) > 255) {
            return 'Название планирования превышает 255 символов.';
        }

        return null;
    }

    private function parseDocumentType(string $type): ?DocumentType
    {
        $documentType = DocumentType::tryFrom($type);

        return $documentType && in_array($documentType, self::DOCUMENT_TYPES, true)
            ? $documentType
            : null;
    }

    private function findFile(MonthlyProductionPlan $plan, int $fileId): ?MonthlyProductionPlanFile
    {
        $file = $this->entityManager->getRepository(MonthlyProductionPlanFile::class)->find($fileId);

        return $file instanceof MonthlyProductionPlanFile && $file->getMonthlyPlan()?->getId() === $plan->getId()
            ? $file
            : null;
    }

    private function currentUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }

    private function decodeJson(Request $request): array
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }

        return is_array($data) ? $data : [];
    }
}
