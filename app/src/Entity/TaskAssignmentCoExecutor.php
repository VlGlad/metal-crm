<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'task_assignment_co_executors')]
#[ORM\UniqueConstraint(name: 'uniq_task_assignment_co_executor', columns: ['assignment_id', 'user_id'])]
class TaskAssignmentCoExecutor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: TaskAssignment::class, inversedBy: 'coExecutors')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?TaskAssignment $assignment = null;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;
    #[ORM\Column(nullable: true)] private ?\DateTimeImmutable $completedAt = null;

    public function getId(): ?int { return $this->id; }
    public function setAssignment(?TaskAssignment $assignment): self { $this->assignment = $assignment; return $this; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
    public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }
    public function complete(): self { $this->completedAt = new \DateTimeImmutable(); return $this; }

    public function canComplete(): bool
    {
        return $this->completedAt === null
            && in_array($this->assignment?->getStatus(), [TaskAssignment::STATUS_ASSIGNED, TaskAssignment::STATUS_IN_PROGRESS], true);
    }
}
