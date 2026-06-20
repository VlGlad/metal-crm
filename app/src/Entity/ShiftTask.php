<?php

namespace App\Entity;

use App\Repository\ShiftTaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShiftTaskRepository::class)]
#[ORM\Table(name: 'shift_tasks')]
class ShiftTask
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'date_immutable')]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(length: 255)]
    private string $workshop = '';

    #[ORM\Column(length: 50)]
    private string $status = 'draft';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $issuedBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $closedBy = null;

    #[ORM\OneToMany(
        mappedBy: 'shiftTask',
        targetEntity: ShiftTaskSection::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'id' => 'ASC'])]
    private Collection $sections;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->sections = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getWorkshop(): string
    {
        return $this->workshop;
    }

    public function setWorkshop(string $workshop): self
    {
        $this->workshop = $workshop;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $allowed = ['draft', 'issued', 'in_progress', 'closed'];

        if (!in_array($status, $allowed, true)) {
            $status = 'draft';
        }

        $this->status = $status;
        return $this;
    }

    /**
     * @return Collection<int, ShiftTaskSection>
     */
    public function getSections(): Collection
    {
        return $this->sections;
    }

    public function addSection(ShiftTaskSection $section): self
    {
        if (!$this->sections->contains($section)) {
            $this->sections->add($section);
            $section->setShiftTask($this);
        }

        return $this;
    }

    public function removeSection(ShiftTaskSection $section): self
    {
        if ($this->sections->removeElement($section)) {
            if ($section->getShiftTask() === $this) {
                $section->setShiftTask(null);
            }
        }

        return $this;
    }

    public function clearSections(): self
    {
        foreach ($this->sections as $section) {
            $this->removeSection($section);
        }

        return $this;
    }

    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getIssuedBy(): ?User
    {
        return $this->issuedBy;
    }

    public function setIssuedBy(?User $issuedBy): self
    {
        $this->issuedBy = $issuedBy;
        return $this;
    }

    public function getClosedBy(): ?User
    {
        return $this->closedBy;
    }

    public function setClosedBy(?User $closedBy): self
    {
        $this->closedBy = $closedBy;
        return $this;
    }
}
