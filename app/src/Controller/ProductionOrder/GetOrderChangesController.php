<?php

namespace App\Controller\ProductionOrder;

use App\Entity\AuditLog;
use App\Entity\User;
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
        if (!$this->access->canViewOrders($user instanceof User ? $user : null)) {
            return $this->json(['message' => 'Нет доступа к заказу.'], 403);
        }

        $before = $request->query->get('before');
        if ($before !== null && (!ctype_digit($before) || (int) $before < 1)) {
            return $this->json(['message' => 'Некорректный курсор истории.'], 422);
        }

        $logs = $this->auditLogs->findOrderChanges($id, $before !== null ? (int) $before : null, self::PAGE_SIZE + 1);
        $hasMore = count($logs) > self::PAGE_SIZE;
        $page = array_slice($logs, 0, self::PAGE_SIZE);

        return $this->json([
            'changes' => array_map($this->serializeChange(...), $page),
            'nextCursor' => $hasMore ? $page[array_key_last($page)]->getId() : null,
        ]);
    }

    private function serializeChange(AuditLog $log): array
    {
        $old = $log->getOldValues() ?? [];
        $new = $log->getNewValues() ?? [];
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
