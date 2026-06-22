<?php

namespace App\Controller;

use App\Entity\ProductionOrder;
use App\Entity\ProductionOrderFile;
use App\Entity\User;
use App\Enum\DocumentType;
use App\Repository\ProductionOrderRepository;
use App\Security\ProductionOrderAccess;
use App\Service\ProductionOrderFileStorage;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders')]
final class ProductionOrderController extends AbstractController
{
    private const DOCUMENT_TYPES = [
        DocumentType::KM_PROJECT,
        DocumentType::ORDER_CALCULATION,
        DocumentType::SPECIFICATION_AND_CONTRACTS,
    ];

    private const DOCUMENT_LABELS = [
        'km_project' => 'КМ',
        'order_calculation' => 'Калькуляция заказа',
        'specification_and_contracts' => 'Заключение спецификации и договоры',
    ];

    public function __construct(
        private readonly ProductionOrderRepository $orders,
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductionOrderAccess $access,
        private readonly ProductionOrderFileStorage $storage,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $user = $this->currentUser();

        if (!$this->access->canViewOrders($user)) {
            return $this->json(['message' => 'Нет доступа к заказам.'], 403);
        }

        return $this->json([
            'orders' => array_map(
                fn (ProductionOrder $order) => $this->serializeOrder($order, $user),
                $this->orders->findBy([], ['id' => 'DESC'])
            ),
            'canCreate' => $this->access->canCreateOrder($user),
        ]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $order = $this->findOrder($id);

        if (!$order) {
            return $this->json(['message' => 'Заказ не найден.'], 404);
        }

        $user = $this->currentUser();

        if (!$this->access->canViewOrders($user)) {
            return $this->json(['message' => 'Нет доступа к заказу.'], 403);
        }

        return $this->json($this->serializeOrder($order, $user));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->currentUser();

        if (!$this->access->canCreateOrder($user)) {
            return $this->json(['message' => 'Нет права создавать заказы.'], 403);
        }

        $data = $this->decodeJson($request);

        if ($error = $this->validateOrderPayload($data)) {
            return $this->json(['message' => $error], 422);
        }

        $order = (new ProductionOrder())
            ->setName((string) $data['name'])
            ->setNumber($data['number'] ?? null)
            ->setCreatedBy($user);

        $this->entityManager->persist($order);

        if ($errorResponse = $this->flushOrder()) {
            return $errorResponse;
        }

        return $this->json($this->serializeOrder($order, $user), 201);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $order = $this->findOrder($id);

        if (!$order) {
            return $this->json(['message' => 'Заказ не найден.'], 404);
        }

        $user = $this->currentUser();

        if (!$this->access->canEditOrder($user)) {
            return $this->json(['message' => 'Нет права редактировать заказ.'], 403);
        }

        $data = $this->decodeJson($request);

        if ($error = $this->validateOrderPayload($data)) {
            return $this->json(['message' => $error], 422);
        }

        $order
            ->setName((string) $data['name'])
            ->setNumber($data['number'] ?? null)
            ->touch();

        if ($errorResponse = $this->flushOrder()) {
            return $errorResponse;
        }

        return $this->json($this->serializeOrder($order, $user));
    }

    #[Route('/{id}/documents/{type}', methods: ['POST'])]
    public function upload(int $id, string $type, Request $request): JsonResponse
    {
        $order = $this->findOrder($id);

        if (!$order) {
            return $this->json(['message' => 'Заказ не найден.'], 404);
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

        if (count($files) > 10) {
            return $this->json(['message' => 'За один раз можно загрузить не более 10 файлов.'], 422);
        }

        $storedNames = [];

        try {
            foreach ($files as $uploadedFile) {
                if (!$uploadedFile instanceof UploadedFile) {
                    throw new \RuntimeException('Передан некорректный файл.');
                }

                $stored = $this->storage->store($uploadedFile, (int) $order->getId());
                $storedNames[] = $stored['storedName'];

                $file = (new ProductionOrderFile())
                    ->setDocumentType($documentType)
                    ->setOriginalName($stored['originalName'])
                    ->setStoredName($stored['storedName'])
                    ->setMimeType($stored['mimeType'])
                    ->setSize($stored['size'])
                    ->setUploadedBy($user);

                $order->addFile($file);
                $this->entityManager->persist($file);
            }

            $order->touch();
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            dd($exception);
            foreach ($storedNames as $storedName) {
                $this->storage->delete($storedName);
            }

            return $this->json([
                'message' => $exception instanceof \RuntimeException
                    ? $exception->getMessage()
                    : 'Не удалось сохранить файлы.',
            ], 422);
        }

        return $this->json($this->serializeOrder($order, $user), 201);
    }

    #[Route('/{orderId}/documents/{fileId}/download', methods: ['GET'])]
    public function download(int $orderId, int $fileId): JsonResponse|BinaryFileResponse
    {
        $order = $this->findOrder($orderId);
        $file = $order ? $this->findFile($order, $fileId) : null;

        if (!$order || !$file) {
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

    #[Route('/{orderId}/documents/{fileId}', methods: ['DELETE'])]
    public function deleteFile(int $orderId, int $fileId): JsonResponse
    {
        $order = $this->findOrder($orderId);
        $file = $order ? $this->findFile($order, $fileId) : null;

        if (!$order || !$file) {
            return $this->json(['message' => 'Файл не найден.'], 404);
        }

        if (!$this->access->canUploadDocument($this->currentUser(), $file->getDocumentType())) {
            return $this->json(['message' => 'Нет права удалять этот файл.'], 403);
        }

        $storedName = $file->getStoredName();
        $order->removeFile($file)->touch();
        $this->entityManager->remove($file);
        $this->entityManager->flush();
        $this->storage->delete($storedName);

        return $this->json($this->serializeOrder($order, $this->currentUser()));
    }

    #[Route('/{id}/issue', methods: ['POST'])]
    public function issue(int $id): JsonResponse
    {
        $order = $this->findOrder($id);

        if (!$order) {
            return $this->json(['message' => 'Заказ не найден.'], 404);
        }

        $user = $this->currentUser();

        if (!$this->access->canIssueOrder($user)) {
            return $this->json(['message' => 'Нет права отдавать заказ в работу.'], 403);
        }

        if ($order->getStatus() === ProductionOrder::STATUS_IN_WORK) {
            return $this->json(['message' => 'Заказ уже отдан в работу.'], 422);
        }

        if (!$order->getNumber()) {
            return $this->json(['message' => 'Перед выдачей в работу укажите номер заказа.'], 422);
        }

        foreach (self::DOCUMENT_TYPES as $type) {
            if (!$this->hasDocumentType($order, $type)) {
                return $this->json([
                    'message' => sprintf('Загрузите хотя бы один документ типа «%s».', self::DOCUMENT_LABELS[$type->value]),
                ], 422);
            }
        }

        $order->issue($user);
        $this->entityManager->flush();

        return $this->json($this->serializeOrder($order, $user));
    }

    private function serializeOrder(ProductionOrder $order, ?User $user): array
    {
        $documents = [];
        $documentPermissions = [];

        foreach (self::DOCUMENT_TYPES as $type) {
            $canView = $this->access->canViewDocument($user, $type);
            $documents[$type->value] = $canView
                ? array_values(array_map(
                    fn (ProductionOrderFile $file) => $this->serializeFile($order, $file),
                    array_filter(
                        $order->getFiles()->toArray(),
                        fn (ProductionOrderFile $file) => $file->getDocumentType() === $type
                    )
                ))
                : null;

            $documentPermissions[$type->value] = [
                'canView' => $canView,
                'canUpload' => $this->access->canUploadDocument($user, $type),
            ];
        }

        return [
            'id' => $order->getId(),
            'number' => $order->getNumber(),
            'name' => $order->getName(),
            'status' => $order->getStatus(),
            'documents' => $documents,
            'permissions' => [
                'canEdit' => $this->access->canEditOrder($user),
                'canIssue' => $this->access->canIssueOrder($user),
                'documents' => $documentPermissions,
            ],
            'createdBy' => $order->getCreatedBy()?->getFullName(),
            'issuedBy' => $order->getIssuedBy()?->getFullName(),
            'issuedAt' => $order->getIssuedAt()?->format(DATE_ATOM),
            'createdAt' => $order->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $order->getUpdatedAt()->format(DATE_ATOM),
        ];
    }

    private function serializeFile(ProductionOrder $order, ProductionOrderFile $file): array
    {
        return [
            'id' => $file->getId(),
            'name' => $file->getOriginalName(),
            'mimeType' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploadedBy' => $file->getUploadedBy()?->getFullName(),
            'uploadedAt' => $file->getUploadedAt()->format(DATE_ATOM),
            'downloadUrl' => sprintf(
                '/orders/%d/documents/%d/download',
                $order->getId(),
                $file->getId()
            ),
        ];
    }

    private function validateOrderPayload(array $data): ?string
    {
        $name = trim((string) ($data['name'] ?? ''));
        $number = trim((string) ($data['number'] ?? ''));

        if ($name === '') {
            return 'Укажите наименование заказа.';
        }

        if (mb_strlen($name) > 255) {
            return 'Наименование заказа превышает 255 символов.';
        }

        if (mb_strlen($number) > 100) {
            return 'Номер заказа превышает 100 символов.';
        }

        return null;
    }

    private function flushOrder(): ?JsonResponse
    {
        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            return $this->json(['message' => 'Заказ с таким номером уже существует.'], 409);
        }

        return null;
    }

    private function hasDocumentType(ProductionOrder $order, DocumentType $type): bool
    {
        foreach ($order->getFiles() as $file) {
            if ($file->getDocumentType() === $type) {
                return true;
            }
        }

        return false;
    }

    private function parseDocumentType(string $type): ?DocumentType
    {
        $documentType = DocumentType::tryFrom($type);

        return $documentType && in_array($documentType, self::DOCUMENT_TYPES, true)
            ? $documentType
            : null;
    }

    private function findOrder(int $id): ?ProductionOrder
    {
        return $this->orders->find($id);
    }

    private function findFile(ProductionOrder $order, int $fileId): ?ProductionOrderFile
    {
        $file = $this->entityManager->getRepository(ProductionOrderFile::class)->find($fileId);

        return $file instanceof ProductionOrderFile && $file->getProductionOrder()?->getId() === $order->getId()
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
