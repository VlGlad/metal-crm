<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'document_workflows')]
class DocumentWorkflow
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_APPROVAL = 'in_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REMARKS = 'remarks';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REVOKED = 'revoked';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(length: 100)]
    private string $documentType = 'common';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 30)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, DocumentWorkflowFile> */
    #[ORM\OneToMany(mappedBy: 'workflow', targetEntity: DocumentWorkflowFile::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['uploadedAt' => 'DESC', 'id' => 'DESC'])]
    private Collection $files;

    /** @var Collection<int, DocumentApprovalStep> */
    #[ORM\OneToMany(mappedBy: 'workflow', targetEntity: DocumentApprovalStep::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'id' => 'ASC'])]
    private Collection $approvalSteps;

    /** @var Collection<int, DocumentWorkflowEvent> */
    #[ORM\OneToMany(mappedBy: 'workflow', targetEntity: DocumentWorkflowEvent::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC', 'id' => 'DESC'])]
    private Collection $events;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->files = new ArrayCollection();
        $this->approvalSteps = new ArrayCollection();
        $this->events = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = trim($title); return $this; }
    public function getDocumentType(): string { return $this->documentType; }
    public function setDocumentType(string $documentType): self { $this->documentType = trim($documentType) ?: 'common'; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $description = trim((string) $description); $this->description = $description === '' ? null : $description; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): self { $this->createdBy = $createdBy; return $this; }
    public function getStartedAt(): ?\DateTimeImmutable { return $this->startedAt; }
    public function setStartedAt(?\DateTimeImmutable $startedAt): self { $this->startedAt = $startedAt; return $this; }
    public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }
    public function setCompletedAt(?\DateTimeImmutable $completedAt): self { $this->completedAt = $completedAt; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    /** @return Collection<int, DocumentWorkflowFile> */ public function getFiles(): Collection { return $this->files; }
    /** @return Collection<int, DocumentApprovalStep> */ public function getApprovalSteps(): Collection { return $this->approvalSteps; }
    /** @return Collection<int, DocumentWorkflowEvent> */ public function getEvents(): Collection { return $this->events; }

    public function addFile(DocumentWorkflowFile $file): self { if (!$this->files->contains($file)) { $this->files->add($file); $file->setWorkflow($this); } return $this; }
    public function removeFile(DocumentWorkflowFile $file): self { if ($this->files->removeElement($file) && $file->getWorkflow() === $this) { $file->setWorkflow(null); } return $this; }
    public function addApprovalStep(DocumentApprovalStep $step): self { if (!$this->approvalSteps->contains($step)) { $this->approvalSteps->add($step); $step->setWorkflow($this); } return $this; }
    public function clearApprovalSteps(): self { foreach ($this->approvalSteps as $step) { $this->approvalSteps->removeElement($step); } return $this; }
    public function addEvent(DocumentWorkflowEvent $event): self { if (!$this->events->contains($event)) { $this->events->add($event); $event->setWorkflow($this); } return $this; }
    public function touch(): self { $this->updatedAt = new \DateTimeImmutable(); return $this; }
}