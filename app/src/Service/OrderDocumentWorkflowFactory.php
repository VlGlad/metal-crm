<?php

namespace App\Service;

use App\Entity\DocumentWorkflow;
use App\Entity\DocumentWorkflowEvent;
use App\Entity\ProductionOrderFile;
use Doctrine\ORM\EntityManagerInterface;

final class OrderDocumentWorkflowFactory
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function create(ProductionOrderFile $file): DocumentWorkflow
    {
        $workflow = (new DocumentWorkflow())
            ->setTitle($file->getOriginalName())
            ->setDocumentType('order')
            ->setProductionOrderFile($file)
            ->setCreatedBy($file->getUploadedBy());

        $workflow->addEvent((new DocumentWorkflowEvent())
            ->setEventType('created')
            ->setComment('Документ создан при загрузке файла заказа.')
            ->setCreatedBy($file->getUploadedBy()));

        $this->entityManager->persist($workflow);

        return $workflow;
    }
}
