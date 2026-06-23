<?php

namespace App\Controller;

use App\Entity\MonthlyProductionPlan;
use App\Entity\User;
use App\Entity\WorkingDocumentationFile;
use App\Entity\WorkingDocumentationPackage;
use App\Enum\DocumentType;
use App\Repository\MonthlyProductionPlanRepository;
use App\Repository\WorkingDocumentationPackageRepository;
use App\Security\WorkingDocumentationAccess;
use App\Service\WorkingDocumentationFileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/working-documents')]
final class WorkingDocumentationController extends AbstractController
{
    public function __construct(
        private readonly WorkingDocumentationPackageRepository $packages,
        private readonly MonthlyProductionPlanRepository $monthlyPlans,
        private readonly EntityManagerInterface $entityManager,
        private readonly WorkingDocumentationAccess $access,
        private readonly WorkingDocumentationFileStorage $storage,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $user = $this->currentUser();

        if (!$this->access->canView($user)) {
            return $this->json(['message' => 'Нет доступа к рабочим документам.'], 403);
        }

        return $this->json([
            'packages' => array_map(
                fn (WorkingDocumentationPackage $package) => $this->serializePackage($package),
                $this->packages->findBy([], ['id' => 'DESC'])
            ),
            'monthlyPlans' => array_map(
                fn (MonthlyProductionPlan $plan) => [
                    'id' => $plan->getId(),
                    'month' => $plan->getMonth()->format('Y-m'),
                    'name' => $plan->getName(),
                ],
                $this->monthlyPlans->findBy([], ['month' => 'DESC', 'id' => 'DESC'])
            ),
            'canCreate' => $this->access->canEdit($user),
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->currentUser();

        if (!$this->access->canEdit($user)) {
            return $this->json(['message' => 'Нет права создавать комплекты документов.'], 403);
        }

        $data = $this->decodeJson($request);
        $validation = $this->validatePayload($data);

        if (is_string($validation)) {
            return $this->json(['message' => $validation], 422);
        }

        $package = (new WorkingDocumentationPackage())
            ->setName((string) $data['name'])
            ->setMonthlyPlan($validation)
            ->setCreatedBy($user);

        $this->entityManager->persist($package);
        $this->entityManager->flush();

        return $this->json($this->serializePackage($package), 201);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $package = $this->packages->find($id);

        if (!$package) {
            return $this->json(['message' => 'Комплект документов не найден.'], 404);
        }

        if (!$this->access->canEdit($this->currentUser())) {
            return $this->json(['message' => 'Нет права редактировать комплект документов.'], 403);
        }

        $data = $this->decodeJson($request);
        $validation = $this->validatePayload($data);

        if (is_string($validation)) {
            return $this->json(['message' => $validation], 422);
        }

        $package
            ->setName((string) $data['name'])
            ->setMonthlyPlan($validation)
            ->touch();

        $this->entityManager->flush();

        return $this->json($this->serializePackage($package));
    }

    #[Route('/{id}/documents', methods: ['POST'])]
    public function upload(int $id, Request $request): JsonResponse
    {
        $package = $this->packages->find($id);

        if (!$package) {
            return $this->json(['message' => 'Комплект документов не найден.'], 404);
        }

        $user = $this->currentUser();

        if (!$this->access->canEdit($user)) {
            return $this->json(['message' => 'Нет права загружать рабочие документы.'], 403);
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

                $stored = $this->storage->store($uploadedFile, (int) $package->getId());
                $storedNames[] = $stored['storedName'];

                $file = (new WorkingDocumentationFile())
                    ->setDocumentType(DocumentType::CUTTING_DRAWINGS_AND_PROGRAMS)
                    ->setOriginalName($stored['originalName'])
                    ->setStoredName($stored['storedName'])
                    ->setMimeType($stored['mimeType'])
                    ->setSize($stored['size'])
                    ->setUploadedBy($user);

                $package->addFile($file);
                $this->entityManager->persist($file);
            }

            $package->touch();
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

        return $this->json($this->serializePackage($package), 201);
    }

    #[Route('/{packageId}/documents/{fileId}/download', methods: ['GET'])]
    public function download(int $packageId, int $fileId): JsonResponse|BinaryFileResponse
    {
        $package = $this->packages->find($packageId);
        $file = $package ? $this->findFile($package, $fileId) : null;

        if (!$package || !$file) {
            return $this->json(['message' => 'Файл не найден.'], 404);
        }

        if (!$this->access->canView($this->currentUser())) {
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

    #[Route('/{packageId}/documents/{fileId}', methods: ['DELETE'])]
    public function deleteFile(int $packageId, int $fileId): JsonResponse
    {
        $package = $this->packages->find($packageId);
        $file = $package ? $this->findFile($package, $fileId) : null;

        if (!$package || !$file) {
            return $this->json(['message' => 'Файл не найден.'], 404);
        }

        if (!$this->access->canEdit($this->currentUser())) {
            return $this->json(['message' => 'Нет права удалять файл.'], 403);
        }

        $storedName = $file->getStoredName();
        $package->removeFile($file)->touch();
        $this->entityManager->remove($file);
        $this->entityManager->flush();
        $this->storage->delete($storedName);

        return $this->json($this->serializePackage($package));
    }

    private function validatePayload(array $data): MonthlyProductionPlan|string
    {
        $name = trim((string) ($data['name'] ?? ''));
        $monthlyPlanId = filter_var($data['monthlyPlanId'] ?? null, FILTER_VALIDATE_INT);

        if ($name === '') {
            return 'Укажите название комплекта документов.';
        }

        if (mb_strlen($name) > 255) {
            return 'Название превышает 255 символов.';
        }

        if (!$monthlyPlanId) {
            return 'Выберите месячное планирование.';
        }

        $monthlyPlan = $this->monthlyPlans->find($monthlyPlanId);

        return $monthlyPlan instanceof MonthlyProductionPlan
            ? $monthlyPlan
            : 'Выбранное месячное планирование не найдено.';
    }

    private function serializePackage(WorkingDocumentationPackage $package): array
    {
        $monthlyPlan = $package->getMonthlyPlan();

        return [
            'id' => $package->getId(),
            'name' => $package->getName(),
            'monthlyPlan' => [
                'id' => $monthlyPlan?->getId(),
                'month' => $monthlyPlan?->getMonth()->format('Y-m'),
                'name' => $monthlyPlan?->getName(),
            ],
            'files' => array_map(
                fn (WorkingDocumentationFile $file) => [
                    'id' => $file->getId(),
                    'name' => $file->getOriginalName(),
                    'mimeType' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploadedBy' => $file->getUploadedBy()?->getFullName(),
                    'uploadedAt' => $file->getUploadedAt()->format(DATE_ATOM),
                    'downloadUrl' => sprintf(
                        '/api/working-documents/%d/documents/%d/download',
                        $package->getId(),
                        $file->getId()
                    ),
                ],
                $package->getFiles()->toArray()
            ),
            'permissions' => [
                'canEdit' => $this->access->canEdit($this->currentUser()),
            ],
            'createdBy' => $package->getCreatedBy()?->getFullName(),
            'createdAt' => $package->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $package->getUpdatedAt()->format(DATE_ATOM),
        ];
    }

    private function findFile(
        WorkingDocumentationPackage $package,
        int $fileId
    ): ?WorkingDocumentationFile {
        $file = $this->entityManager->getRepository(WorkingDocumentationFile::class)->find($fileId);

        return $file instanceof WorkingDocumentationFile && $file->getPackage()?->getId() === $package->getId()
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
