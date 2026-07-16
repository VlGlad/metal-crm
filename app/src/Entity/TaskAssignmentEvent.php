<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'task_assignment_events')]
class TaskAssignmentEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: TaskAssignment::class, inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?TaskAssignment $assignment = null;
    #[ORM\Column(length: 50)] private string $eventType = '';
    #[ORM\Column(type: 'text', nullable: true)] private ?string $comment = null;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;
    #[ORM\Column] private \DateTimeImmutable $createdAt;
    public function __construct() { $this->createdAt = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getAssignment(): ?TaskAssignment { return $this->assignment; }
    public function setAssignment(?TaskAssignment $assignment): self { $this->assignment = $assignment; return $this; }
    public function getEventType(): string { return $this->eventType; }
    public function setEventType(string $eventType): self { $this->eventType = $eventType; return $this; }
    public function getComment(): ?string { return $this->comment; }
    public function setComment(?string $comment): self { $comment = trim((string) $comment); $this->comment = $comment === '' ? null : $comment; return $this; }
    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): self { $this->createdBy = $createdBy; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}