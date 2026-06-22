<?php

namespace App\Entity;

use App\Repository\MonthlyProductionPlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MonthlyProductionPlanRepository::class)]
#[ORM\Table(name: 'monthly_production_plans')]
class MonthlyProductionPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $month;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, MonthlyProductionPlanFile>
     */
    #[ORM\OneToMany(
        mappedBy: 'monthlyPlan',
        targetEntity: MonthlyProductionPlanFile::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['uploadedAt' => 'DESC', 'id' => 'DESC'])]
    private Collection $files;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->month = $now->modify('first day of this month')->setTime(0, 0);
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->files = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMonth(): \DateTimeImmutable
    {
        return $this->month;
    }

    public function setMonth(\DateTimeImmutable $month): self
    {
        $this->month = $month->modify('first day of this month')->setTime(0, 0);

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, MonthlyProductionPlanFile>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(MonthlyProductionPlanFile $file): self
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setMonthlyPlan($this);
        }

        return $this;
    }

    public function removeFile(MonthlyProductionPlanFile $file): self
    {
        if ($this->files->removeElement($file) && $file->getMonthlyPlan() === $this) {
            $file->setMonthlyPlan(null);
        }

        return $this;
    }

    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
