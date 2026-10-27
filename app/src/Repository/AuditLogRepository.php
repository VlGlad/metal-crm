<?php

namespace App\Repository;

use App\Entity\AuditLog;
use App\Enum\AuditEntityType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    /** @return list<AuditLog> */
    public function findOrderChanges(int $orderId, ?int $beforeId, int $limit): array
    {
        $query = $this->createQueryBuilder('audit')
            ->leftJoin('audit.user', 'actor')
            ->addSelect('actor')
            ->andWhere('audit.entityType = :entityType')
            ->andWhere('audit.entityId = :orderId')
            ->setParameter('entityType', AuditEntityType::ProductionOrder->value)
            ->setParameter('orderId', $orderId)
            ->orderBy('audit.id', 'DESC')
            ->setMaxResults($limit);

        if ($beforeId !== null) {
            $query->andWhere('audit.id < :beforeId')->setParameter('beforeId', $beforeId);
        }

        return $query->getQuery()->getResult();
    }
}
