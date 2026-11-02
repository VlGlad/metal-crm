<?php

namespace App\Service;

use App\Entity\DocumentWorkflow;
use App\Entity\ProductionOrder;
use App\Enum\ProductionOrderStatus;
use Doctrine\ORM\EntityManagerInterface;

final class ProductionOrderApprovalStatus
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function recalculate(ProductionOrder $order, ?DocumentWorkflow $changedWorkflow = null): void
    {
        if (!in_array($order->getStatus(), [ProductionOrderStatus::DRAFT, ProductionOrderStatus::POTENTIAL], true)) {
            return;
        }

        if ($order->getFiles()->isEmpty()) {
            $order->syncApprovalStatus(false);
            return;
        }

        foreach ($order->getFiles() as $file) {
            $workflow = $changedWorkflow?->getProductionOrderFile() === $file
                ? $changedWorkflow
                : $this->entityManager->getRepository(DocumentWorkflow::class)
                    ->findOneBy(['productionOrderFile' => $file]);

            if (!$workflow || $workflow->getStatus() !== DocumentWorkflow::STATUS_APPROVED) {
                $order->syncApprovalStatus(false);
                return;
            }
        }

        $order->syncApprovalStatus(true);
    }
}
