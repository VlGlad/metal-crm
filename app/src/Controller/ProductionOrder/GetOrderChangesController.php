<?php

namespace App\Controller\ProductionOrder;

use App\Entity\AuditLog;
use App\Entity\User;
use App\Enum\AuditEntityType;
use App\Enum\DocumentType;
use App\Repository\AuditLogRepository;
use App\Repository\ProductionOrderRepository;
use App\Security\ProductionOrderAccess;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class GetOrderChangesController extends AbstractController
{
    private const PAGE_SIZE = 50;
    private const SCAN_BATCH_SIZE = 100;
    private const MAX_SCANNED_LOGS = 500;

    private const VISIBLE_FIELDS = ['number', 'name', 'status', 'issuedAt'];

    public function __construct(
        private readonly ProductionOrderRepository $orders,
        private readonly AuditLogRepository $auditLogs,
        private readonly ProductionOrderAccess $access,
    ) {
    }

    #[Route('/api/orders/{id}/changes', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        if ($this->orders->find($id) === null) {
            return $this->json(['message' => 'Заказ не найден.'], 404);
        }

        $user = $this->getUser();
        $user = $user instanceof User ? $user : null;
        if (!$this->access->canViewOrders($user)) {
            return $this->json(['message' => 'Нет доступа к заказу.'], 403);
        }

        $before = $request->query->get('before');
        if ($before !== null && (!ctype_digit($before) || (int) $before < 1)) {
            return $this->json(['message' => 'Некорректный курсор истории.'], 422);
        }

        $changes = [];
        $cursor = $before !== null ? (int) $before : null;
        $scanned = 0;
        $nextCursor = null;

        do {
            $logs = $this->auditLogs->findOrderChanges($id, $cursor, self::SCAN_BATCH_SIZE);
            foreach ($logs as $index => $log) {
                $cursor = $log->getId();
                $scanned++;

                if ($this->canViewChange($log, $user)) {
                    $changes[] = $this->serializeChange($log);
                }

                if (count($changes) === self::PAGE_SIZE || $scanned === self::MAX_SCANNED_LOGS) {
                    $nextCursor = $index < count($logs) - 1 || count($logs) === self::SCAN_BATCH_SIZE
                        ? $cursor
                        : null;
                    break 2;
                }
            }
        } while (count($logs) === self::SCAN_BATCH_SIZE);

        return $this->json([
            'changes' => $changes,
            'nextCursor' => $nextCursor,
        ]);
    }

    private function canViewChange(AuditLog $log, ?User $user): bool
    {
        if ($log->getEntityType() !== AuditEntityType::ProductionOrderFile) {
            return true;
        }

        $documentType = DocumentType::tryFrom((string) (
            $log->getNewValues()['documentType']
                ?? $log->getOldValues()['documentType']
                ?? ''
        ));

        return $documentType !== null && $this->access->canViewDocument($user, $documentType);
    }

    private function serializeChange(AuditLog $log): array
    {
        $old = $log->getOldValues() ?? [];
        $new = $log->getNewValues() ?? [];

        if ($log->getEntityType() === AuditEntityType::ProductionOrderFile) {
            return [
                'id' => $log->getId(),
                'action' => match ($log->getAction()) {
                    'create' => 'file_upload',
                    'delete' => 'file_delete',
                    default => 'file_update',
                },
                'createdAt' => $log->getCreatedAt()->format(DATE_ATOM),
                'actor' => $log->getUser()?->getFullName(),
                'fields' => [[
                    'field' => 'document',
                    'documentType' => $new['documentType'] ?? $old['documentType'] ?? null,
                    'oldValue' => $old['originalName'] ?? null,
                    'newValue' => $new['originalName'] ?? null,
                ]],
            ];
        }

        $fields = [];

        foreach (self::VISIBLE_FIELDS as $field) {
            if (array_key_exists($field, $old) || array_key_exists($field, $new)) {
                $fields[] = [
                    'field' => $field,
                    'oldValue' => $old[$field] ?? null,
                    'newValue' => $new[$field] ?? null,
                ];
            }
        }

        return [
            'id' => $log->getId(),
            'action' => $log->getAction(),
            'createdAt' => $log->getCreatedAt()->format(DATE_ATOM),
            'actor' => $log->getUser()?->getFullName(),
            'fields' => $fields,
        ];
    }
}
