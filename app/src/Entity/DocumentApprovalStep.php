<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'document_approval_steps')]
class DocumentApprovalStep
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REMARKS = 'remarks';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: DocumentWorkflow::class, inversedBy: 'approvalSteps')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DocumentWorkflow $workflow = null;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $approver = null;
    #[ORM\Column] private int $sortOrder = 0;
    #[ORM\Column(length: 30)] private string $status = self::STATUS_PENDING;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $comment = null;
    #[ORM\Column(nullable: true)] private ?\DateTimeImmutable $decidedAt = null;

    public function getId(): ?int { return $this->id; }
    public function getWorkflow(): ?DocumentWorkflow { return $this->workflow; }
    public function setWorkflow(?DocumentWorkflow $workflow): self { $this->workflow = $workflow; return $this; }
    public function getApprover(): ?User { return $this->approver; }
    public function setApprover(?User $approver): self { $this->approver = $approver; return $this; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): self { $this->sortOrder = $sortOrder; return $this; }
    public function getStatus(): string { return $this->status; }
    public function getComment(): ?string { return $this->comment; }
    public function getDecidedAt(): ?\DateTimeImmutable { return $this->decidedAt; }
    public function decide(string $status, ?string $comment): self { $this->status = $status; $comment = trim((string) $comment); $this->comment = $comment === '' ? null : $comment; $this->decidedAt = new \DateTimeImmutable(); return $this; }
}