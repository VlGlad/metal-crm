<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'material_procurement_request_events')]
class MaterialProcurementRequestEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MaterialProcurementRequest::class, inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?MaterialProcurementRequest $request = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $fromStatus = null;

    #[ORM\Column(length: 30)]
    private string $toStatus;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getRequest(): ?MaterialProcurementRequest { return $this->request; }

    public function setRequest(?MaterialProcurementRequest $request): self
    {
        $this->request = $request;
        return $this;
    }

    public function getFromStatus(): ?string { return $this->fromStatus; }

    public function setFromStatus(?string $fromStatus): self
    {
        $this->fromStatus = $fromStatus;
        return $this;
    }

    public function getToStatus(): string { return $this->toStatus; }

    public function setToStatus(string $toStatus): self
    {
        $this->toStatus = $toStatus;
        return $this;
    }

    public function getComment(): ?string { return $this->comment; }

    public function setComment(?string $comment): self
    {
        $comment = trim((string) $comment);
        $this->comment = $comment === '' ? null : $comment;
        return $this;
    }

    public function getCreatedBy(): ?User { return $this->createdBy; }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}