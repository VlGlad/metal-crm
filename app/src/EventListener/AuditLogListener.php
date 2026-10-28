<?php

namespace App\EventListener;

use App\Entity\User;
use App\Entity\ProductionOrderFile;
use App\Enum\AuditEntityType;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::preRemove)]
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
final class AuditLogListener
{
    /** @var array<int, array{type: AuditEntityType, old: array, new: array, parentType: ?AuditEntityType, parentId: ?int}> */
    private array $updates = [];

    /** @var array<int, array{type: AuditEntityType, id: int, old: array, parentType: ?AuditEntityType, parentId: ?int}> */
    private array $deletions = [];

    /** @var array<int, int> */
    private array $removedFileOrderIds = [];

    public function __construct(private readonly Security $security)
    {
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof ProductionOrderFile && $entity->getProductionOrder()?->getId() !== null) {
            $this->removedFileOrderIds[spl_object_id($entity)] = $entity->getProductionOrder()->getId();
        }
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $this->updates = [];
        $this->deletions = [];

        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $type = $this->entityType($em, $entity);
            if ($type === null) {
                continue;
            }

            $old = [];
            $new = [];
            foreach ($uow->getEntityChangeSet($entity) as $field => [$before, $after]) {
                if ($field === 'updatedAt') {
                    continue;
                }

                $old[$field] = $this->normalize($em, $before);
                $new[$field] = $this->normalize($em, $after);
            }

            if ($old !== []) {
                [$parentType, $parentId] = $this->parent($entity);
                $this->updates[spl_object_id($entity)] = [
                    'type' => $type,
                    'old' => $old,
                    'new' => $new,
                    'parentType' => $parentType,
                    'parentId' => $parentId,
                ];
            }
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $type = $this->entityType($em, $entity);
            if ($type !== null) {
                [$parentType, $parentId] = $this->parent($entity);
                $this->deletions[spl_object_id($entity)] = [
                    'type' => $type,
                    'id' => $this->entityId($em, $entity),
                    'old' => $this->snapshot($em, $entity),
                    'parentType' => $parentType,
                    'parentId' => $parentId,
                ];
            }
        }

        $this->removedFileOrderIds = [];
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $entity = $args->getObject();
        $type = $this->entityType($em, $entity);
        if ($type !== null) {
            [$parentType, $parentId] = $this->parent($entity);
            $this->write($em, 'create', $type, $this->entityId($em, $entity), null, $this->snapshot($em, $entity), $parentType, $parentId);
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        $key = spl_object_id($entity);
        if (!isset($this->updates[$key])) {
            return;
        }

        $change = $this->updates[$key];
        unset($this->updates[$key]);
        $this->write($args->getObjectManager(), 'update', $change['type'], $this->entityId($args->getObjectManager(), $entity), $change['old'], $change['new'], $change['parentType'], $change['parentId']);
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        $key = spl_object_id($entity);
        if (!isset($this->deletions[$key])) {
            return;
        }

        $change = $this->deletions[$key];
        unset($this->deletions[$key]);
        $this->write($args->getObjectManager(), 'delete', $change['type'], $change['id'], $change['old'], null, $change['parentType'], $change['parentId']);
    }

    private function entityType(EntityManagerInterface $em, object $entity): ?AuditEntityType
    {
        return AuditEntityType::tryFrom($em->getClassMetadata($entity::class)->getName());
    }

    /** @return array{?AuditEntityType, ?int} */
    private function parent(object $entity): array
    {
        if ($entity instanceof ProductionOrderFile) {
            $orderId = $entity->getProductionOrder()?->getId()
                ?? $this->removedFileOrderIds[spl_object_id($entity)]
                ?? null;

            return $orderId !== null
                ? [AuditEntityType::ProductionOrder, $orderId]
                : [null, null];
        }

        return [null, null];
    }

    private function snapshot(EntityManagerInterface $em, object $entity): array
    {
        $metadata = $em->getClassMetadata($entity::class);
        $values = [];

        foreach ($metadata->getFieldNames() as $field) {
            if ($field !== 'updatedAt') {
                $values[$field] = $this->normalize($em, $metadata->getFieldValue($entity, $field));
            }
        }

        foreach ($metadata->getAssociationNames() as $field) {
            if ($metadata->isSingleValuedAssociation($field)) {
                $values[$field] = $this->normalize($em, $metadata->getFieldValue($entity, $field));
            }
        }

        return $values;
    }

    private function normalize(EntityManagerInterface $em, mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }
        if (is_array($value)) {
            return array_map(fn (mixed $item) => $this->normalize($em, $item), $value);
        }
        if (is_object($value)) {
            return $em->getClassMetadata($value::class)->getIdentifierValues($value);
        }

        return $value;
    }

    private function entityId(EntityManagerInterface $em, object $entity): int
    {
        $id = $em->getClassMetadata($entity::class)->getIdentifierValues($entity);

        return (int) reset($id);
    }

    private function write(
        EntityManagerInterface $em,
        string $action,
        AuditEntityType $type,
        int $entityId,
        ?array $old,
        ?array $new,
        ?AuditEntityType $parentType,
        ?int $parentId,
    ): void {
        $user = $this->security->getUser();

        $em->getConnection()->insert('audit_logs', [
            'user_id' => $user instanceof User ? $user->getId() : null,
            'action' => $action,
            'entity_type' => $type->value,
            'entity_id' => $entityId,
            'parent_entity_type' => $parentType?->value,
            'parent_entity_id' => $parentId,
            'old_values' => $old,
            'new_values' => $new,
            'context' => null,
            'created_at' => new \DateTimeImmutable(),
        ], [
            'old_values' => Types::JSON,
            'new_values' => Types::JSON,
            'created_at' => Types::DATETIME_IMMUTABLE,
        ]);
    }
}
