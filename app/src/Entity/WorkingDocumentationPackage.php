<?php

namespace App\Entity;

use App\Repository\WorkingDocumentationPackageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkingDocumentationPackageRepository::class)]
#[ORM\Table(name: 'working_documentation_packages')]
class WorkingDocumentationPackage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\ManyToOne(targetEntity: MonthlyProductionPlan::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?MonthlyProductionPlan $monthlyPlan = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, WorkingDocumentationFile>
     */
    #[ORM\OneToMany(
        mappedBy: 'package',
        targetEntity: WorkingDocumentationFile::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['uploadedAt' => 'DESC', 'id' => 'DESC'])]
    private Collection $files;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->files = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }

    public function setName(string $name): self
    {
        $this->name = trim($name);
        return $this;
    }

    public function getMonthlyPlan(): ?MonthlyProductionPlan { return $this->monthlyPlan; }

    public function setMonthlyPlan(MonthlyProductionPlan $monthlyPlan): self
    {
        $this->monthlyPlan = $monthlyPlan;
        return $this;
    }

    public function getCreatedBy(): ?User { return $this->createdBy; }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    /** @return Collection<int, WorkingDocumentationFile> */
    public function getFiles(): Collection { return $this->files; }

    public function addFile(WorkingDocumentationFile $file): self
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setPackage($this);
        }

        return $this;
    }

    public function removeFile(WorkingDocumentationFile $file): self
    {
        if ($this->files->removeElement($file) && $file->getPackage() === $this) {
            $file->setPackage(null);
        }

        return $this;
    }

    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
}
