<?php

namespace App\Entity;

use App\Enum\AuditEntityType;
use App\Repository\AuditLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_logs')]
#[ORM\Index(columns: ['entity_type', 'entity_id'], name: 'idx_audit_entity')]
#[ORM\Index(columns: ['parent_entity_type', 'parent_entity_id'], name: 'idx_audit_parent_entity')]
#[ORM\Index(columns: ['action'], name: 'idx_audit_action')]
#[ORM\Index(columns: ['created_at'], name: 'idx_audit_created_at')]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 80)]
    private string $action;

    #[ORM\Column(length: 120, enumType: AuditEntityType::class)]
    private AuditEntityType $entityType;

    #[ORM\Column(nullable: true)]
    private ?int $entityId = null;

    #[ORM\Column(length: 120, enumType: AuditEntityType::class, nullable: true)]
    private ?AuditEntityType $parentEntityType = null;

    #[ORM\Column(nullable: true)]
    private ?int $parentEntityId = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $oldValues = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $newValues = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $context = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $action,
        AuditEntityType $entityType,
        ?int $entityId = null
    ) {
        $this->action = $action;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function getEntityType(): AuditEntityType
    {
        return $this->entityType;
    }

    public function setEntityType(AuditEntityType $entityType): self
    {
        $this->entityType = $entityType;
        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): self
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function getParentEntityType(): ?AuditEntityType
    {
        return $this->parentEntityType;
    }

    public function setParentEntityType(?AuditEntityType $parentEntityType): self
    {
        $this->parentEntityType = $parentEntityType;
        return $this;
    }

    public function getParentEntityId(): ?int
    {
        return $this->parentEntityId;
    }

    public function setParentEntityId(?int $parentEntityId): self
    {
        $this->parentEntityId = $parentEntityId;
        return $this;
    }

    public function getOldValues(): ?array
    {
        return $this->oldValues;
    }

    public function setOldValues(?array $oldValues): self
    {
        $this->oldValues = $oldValues;
        return $this;
    }

    public function getNewValues(): ?array
    {
        return $this->newValues;
    }

    public function setNewValues(?array $newValues): self
    {
        $this->newValues = $newValues;
        return $this;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function setContext(?array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
