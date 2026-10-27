<?php

namespace App\Tests\EventListener;

use App\Entity\ProductionOrder;
use App\Entity\User;
use App\EventListener\AuditLogListener;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

final class AuditLogListenerTest extends TestCase
{
    public function testCreationIsAuditedAfterIdIsAssigned(): void
    {
        $order = (new ProductionOrder())->setNumber('A-1')->setName('Заказ');
        [$listener, $em, $connection, $metadata] = $this->listenerFor($order);

        $metadata->method('getFieldNames')->willReturn(['id', 'number', 'name']);
        $metadata->method('getFieldValue')->willReturnCallback(
            fn (object $entity, string $field) => match ($field) {
                'id' => 42,
                'number' => $entity->getNumber(),
                'name' => $entity->getName(),
            }
        );
        $metadata->method('getAssociationNames')->willReturn([]);
        $connection->expects(self::once())->method('insert')->with(
            'audit_logs',
            self::callback(static fn (array $row) => $row['action'] === 'create'
                && $row['entity_id'] === 42
                && $row['entity_type'] === ProductionOrder::class
                && $row['old_values'] === null
                && $row['new_values']['number'] === 'A-1'),
            self::anything()
        );

        $listener->postPersist(new PostPersistEventArgs($order, $em));
    }

    public function testOnlyChangedBusinessFieldsAreAudited(): void
    {
        $order = (new ProductionOrder())->setName('Новое название');
        [$listener, $em, $connection] = $this->listenerFor($order);
        $uow = $this->createMock(UnitOfWork::class);
        $em->method('getUnitOfWork')->willReturn($uow);
        $uow->method('getScheduledEntityUpdates')->willReturn([$order]);
        $uow->method('getScheduledEntityDeletions')->willReturn([]);
        $uow->method('getEntityChangeSet')->willReturn([
            'name' => ['Старое название', 'Новое название'],
            'updatedAt' => [new \DateTimeImmutable('-1 minute'), new \DateTimeImmutable()],
        ]);
        $connection->expects(self::once())->method('insert')->with(
            'audit_logs',
            self::callback(static fn (array $row) => $row['action'] === 'update'
                && $row['old_values'] === ['name' => 'Старое название']
                && $row['new_values'] === ['name' => 'Новое название']),
            self::anything()
        );

        $listener->onFlush(new OnFlushEventArgs($em));
        $listener->postUpdate(new PostUpdateEventArgs($order, $em));
    }

    public function testDeletionKeepsIdCapturedBeforePostRemove(): void
    {
        $order = (new ProductionOrder())->setName('Удалённый заказ');
        [$listener, $em, $connection, $metadata] = $this->listenerFor($order);
        $uow = $this->createMock(UnitOfWork::class);
        $em->method('getUnitOfWork')->willReturn($uow);
        $uow->method('getScheduledEntityUpdates')->willReturn([]);
        $uow->method('getScheduledEntityDeletions')->willReturn([$order]);
        $metadata->method('getFieldNames')->willReturn(['name']);
        $metadata->method('getFieldValue')->willReturn('Удалённый заказ');
        $metadata->method('getAssociationNames')->willReturn([]);
        $connection->expects(self::once())->method('insert')->with(
            'audit_logs',
            self::callback(static fn (array $row) => $row['action'] === 'delete'
                && $row['entity_id'] === 42
                && $row['old_values'] === ['name' => 'Удалённый заказ']
                && $row['new_values'] === null),
            self::anything()
        );

        $listener->onFlush(new OnFlushEventArgs($em));
        $listener->postRemove(new PostRemoveEventArgs($order, $em));
    }

    public function testEntityMissingFromEnumIsIgnored(): void
    {
        $user = new User();
        [$listener, $em, $connection] = $this->listenerFor($user);
        $connection->expects(self::never())->method('insert');

        $listener->postPersist(new PostPersistEventArgs($user, $em));
    }

    private function listenerFor(object $entity): array
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);
        $listener = new AuditLogListener($security);
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getName')->willReturn($entity::class);
        $metadata->method('getIdentifierValues')->willReturn(['id' => 42]);
        $em->method('getClassMetadata')->willReturn($metadata);
        $em->method('getConnection')->willReturn($connection);

        return [$listener, $em, $connection, $metadata];
    }
}
