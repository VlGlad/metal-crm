<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'task_assignments')]
class TaskAssignment
{
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 255)] private string $title = '';
    #[ORM\Column(type: 'text', nullable: true)] private ?string $description = null;
    #[ORM\Column(length: 30)] private string $status = self::STATUS_ASSIGNED;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $responsible = null;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;
    #[ORM\ManyToOne(targetEntity: DocumentWorkflow::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?DocumentWorkflow $documentWorkflow = null;
    #[ORM\Column(type: 'date_immutable')] private \DateTimeImmutable $dueDate;
    #[ORM\Column(nullable: true)] private ?\DateTimeImmutable $completedAt = null;
    #[ORM\Column] private \DateTimeImmutable $createdAt;
    #[ORM\Column] private \DateTimeImmutable $updatedAt;
    /** @var Collection<int, TaskAssignmentEvent> */
    #[ORM\OneToMany(mappedBy: 'assignment', targetEntity: TaskAssignmentEvent::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC', 'id' => 'DESC'])]
    private Collection $events;

    public function __construct() { $now = new \DateTimeImmutable(); $this->createdAt = $now; $this->updatedAt = $now; $this->dueDate = $now; $this->events = new ArrayCollection(); }
    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = trim($title); return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $description = trim((string) $description); $this->description = $description === '' ? null : $description; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getResponsible(): ?User { return $this->responsible; }
    public function setResponsible(?User $responsible): self { $this->responsible = $responsible; return $this; }
    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): self { $this->createdBy = $createdBy; return $this; }
    public function getDocumentWorkflow(): ?DocumentWorkflow { return $this->documentWorkflow; }
    public function setDocumentWorkflow(?DocumentWorkflow $documentWorkflow): self { $this->documentWorkflow = $documentWorkflow; return $this; }
    public function getDueDate(): \DateTimeImmutable { return $this->dueDate; }
    public function setDueDate(\DateTimeImmutable $dueDate): self { $this->dueDate = $dueDate->setTime(0,0); return $this; }
    public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }
    public function setCompletedAt(?\DateTimeImmutable $completedAt): self { $this->completedAt = $completedAt; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    /** @return Collection<int, TaskAssignmentEvent> */ public function getEvents(): Collection { return $this->events; }
    public function addEvent(TaskAssignmentEvent $event): self { if (!$this->events->contains($event)) { $this->events->add($event); $event->setAssignment($this); } return $this; }
    public function touch(): self { $this->updatedAt = new \DateTimeImmutable(); return $this; }
}