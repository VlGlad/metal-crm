<?php

namespace App\Controller;

use App\Entity\MaterialProcurementRequest;
use App\Entity\MaterialProcurementRequestFile;
use App\Entity\ProductionOrder;
use App\Entity\User;
use App\Enum\DocumentType;
use App\Repository\MaterialProcurementRequestRepository;
use App\Repository\ProductionOrderRepository;
use App\Security\MaterialProcurementRequestAccess;
use App\Service\MaterialProcurementRequestFileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/procurement-requests')]
final class MaterialProcurementRequestController extends AbstractController
{
    private const MONTH_NAMES = [
        1 => 'январь',
        2 => 'февраль',
        3 => 'март',
        4 => 'апрель',
        5 => 'май',
        6 => 'июнь',
        7 => 'июль',
        8 => 'август',
        9 => 'сентябрь',
        10 => 'октябрь',
        11 => 'ноябрь',
        12 => 'декабрь',
    ];

    public function __construct(
        private readonly MaterialProcurementRequestRepository $requests,
        private readonly ProductionOrderRepository $orders,
        private readonly EntityManagerInterface $entityManager,
        private readonly MaterialProcurementRequestAccess $access,
        private readonly MaterialProcurementRequestFileStorage $storage,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $user = $this->currentUser();

        if (!$this->access->canAccess($user)) {
            return $this->json(['message' => 'Нет доступа к заявкам на ТМЦ.'], 403);
        }

        return $this->json([
            'requests' => array_map(
                fn (MaterialProcurementRequest $request) => $this->serializeRequest($request),
                $this->requests->findBy([], ['month' => 'DESC', 'id' => 'DESC'])
            ),
            'orderOptions' => array_map(
                fn (ProductionOrder $order) => $this->serializeOrderOption($order),
                $this->orders->findBy([], ['id' => 'DESC'])
            ),
            'canCreate' => true,
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->currentUser();

        if (!$this->access->canAccess($user)) {
            return $this->json(['message' => 'Нет права создавать заявки.'], 403);
        }

        $data = $this->decodeJson($request);
        $validation = $this->validatePayload($data);

        if (is_string($validation)) {
            return $this->json(['message' => $validation], 422);
        }

        $entity = (new MaterialProcurementRequest())
            ->setMonth(new \DateTimeImmutable($data['month'].'-01'))
            ->replaceOrders($validation)
            ->setCreatedBy($user);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $this->json($this->serializeRequest($entity), 201);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $entity = $this->requests->find($id);

        if (!$entity) {
            return $this->json(['message' => 'Заявка не найдена.'], 404);
        }

        if (!$this->access->canAccess($this->currentUser())) {
            return $this->json(['message' => 'Нет права редактировать заявку.'], 403);
        }

        $data = $this->decodeJson($request);
        $validation = $this->validatePayload($data);

        if (is_string($validation)) {
            return $this->json(['message' => $validation], 422);
        }

        $entity
            ->setMonth(new \DateTimeImmutable($data['month'].'-01'))
            ->replaceOrders($validation)
            ->touch();

        $this->entityManager->flush();

        return $this->json($this->serializeRequest($entity));
    }

    #[Route('/{id}/documents', methods: ['POST'])]
    public function upload(int $id, Request $request): JsonResponse
    {
        $entity = $this->requests->find($id);

        if (!$entity) {
            return $this->json(['message' => 'Заявка не найдена.'], 404);
        }

        $user = $this->currentUser();

        if (!$this->access->canAccess($user)) {
            return $this->json(['message' => 'Нет права загружать документы.'], 403);
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

                $stored = $this->storage->store($uploadedFile, (int) $entity->getId());
                $storedNames[] = $stored['storedName'];

                $file = (new MaterialProcurementRequestFile())
                    ->setDocumentType(DocumentType::MATERIAL_PROCUREMENT_REQUEST)
                    ->setOriginalName($stored['originalName'])
                    ->setStoredName($stored['storedName'])
                    ->setMimeType($stored['mimeType'])
                    ->setSize($stored['size'])
                    ->setUploadedBy($user);

                $entity->addFile($file);
                $this->entityManager->persist($file);
            }

            $entity->touch();
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

        return $this->json($this->serializeRequest($entity), 201);
    }

    #[Route('/{requestId}/documents/{fileId}/download', methods: ['GET'])]
    public function download(int $requestId, int $fileId): JsonResponse|BinaryFileResponse
    {
        $entity = $this->requests->find($requestId);
        $file = $entity ? $this->findFile($entity, $fileId) : null;

        if (!$entity || !$file) {
            return $this->json(['message' => 'Файл не найден.'], 404);
        }

        if (!$this->access->canAccess($this->currentUser())) {
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

    #[Route('/{requestId}/documents/{fileId}', methods: ['DELETE'])]
    public function deleteFile(int $requestId, int $fileId): JsonResponse
    {
        $entity = $this->requests->find($requestId);
        $file = $entity ? $this->findFile($entity, $fileId) : null;

        if (!$entity || !$file) {
            return $this->json(['message' => 'Файл не найден.'], 404);
        }

        if (!$this->access->canAccess($this->currentUser())) {
            return $this->json(['message' => 'Нет права удалять файл.'], 403);
        }

        $storedName = $file->getStoredName();
        $entity->removeFile($file)->touch();
        $this->entityManager->remove($file);
        $this->entityManager->flush();
        $this->storage->delete($storedName);

        return $this->json($this->serializeRequest($entity));
    }

    private function validatePayload(array $data): array|string
    {
        $month = (string) ($data['month'] ?? '');

        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return 'Укажите корректный месяц.';
        }

        if (!is_array($data['orderIds'] ?? null)) {
            return 'Выберите заказы для заявки.';
        }

        $orderIds = array_values(array_unique(array_filter(
            array_map('intval', $data['orderIds']),
            fn (int $id) => $id > 0
        )));

        if (!$orderIds) {
            return 'Выберите хотя бы один заказ.';
        }

        $orders = $this->orders->findBy(['id' => $orderIds]);

        return count($orders) === count($orderIds)
            ? $orders
            : 'Один или несколько выбранных заказов не найдены.';
    }

    private function serializeRequest(MaterialProcurementRequest $request): array
    {
        return [
            'id' => $request->getId(),
            'month' => $request->getMonth()->format('Y-m'),
            'displayName' => $this->displayName($request),
            'orders' => array_map(
                fn (ProductionOrder $order) => $this->serializeOrderOption($order),
                $request->getOrders()->toArray()
            ),
            'files' => array_map(
                fn (MaterialProcurementRequestFile $file) => [
                    'id' => $file->getId(),
                    'name' => $file->getOriginalName(),
                    'mimeType' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploadedBy' => $file->getUploadedBy()?->getFullName(),
                    'uploadedAt' => $file->getUploadedAt()->format(DATE_ATOM),
                    'downloadUrl' => sprintf(
                        '/api/procurement-requests/%d/documents/%d/download',
                        $request->getId(),
                        $file->getId()
                    ),
                ],
                $request->getFiles()->toArray()
            ),
            'permissions' => ['canEdit' => $this->access->canAccess($this->currentUser())],
            'createdBy' => $request->getCreatedBy()?->getFullName(),
            'createdAt' => $request->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $request->getUpdatedAt()->format(DATE_ATOM),
        ];
    }

    private function serializeOrderOption(ProductionOrder $order): array
    {
        return [
            'id' => $order->getId(),
            'number' => $order->getNumber(),
            'name' => $order->getName(),
        ];
    }

    private function displayName(MaterialProcurementRequest $request): string
    {
        $month = (int) $request->getMonth()->format('n');
        $year = $request->getMonth()->format('Y');

        return sprintf(
            'Заявка на приобретение ТМЦ — %s %s',
            self::MONTH_NAMES[$month],
            $year
        );
    }

    private function findFile(
        MaterialProcurementRequest $request,
        int $fileId
    ): ?MaterialProcurementRequestFile {
        $file = $this->entityManager->getRepository(MaterialProcurementRequestFile::class)->find($fileId);

        return $file instanceof MaterialProcurementRequestFile && $file->getRequest()?->getId() === $request->getId()
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
