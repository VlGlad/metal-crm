<?php

namespace App\Entity;

use App\Repository\ProductionOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductionOrderRepository::class)]
#[ORM\Table(name: 'production_orders')]
#[ORM\UniqueConstraint(name: 'uniq_production_orders_number', columns: ['number'])]
class ProductionOrder
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_WORK = 'in_work';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $number = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(length: 30)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $issuedBy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $issuedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, ProductionOrderFile>
     */
    #[ORM\OneToMany(
        mappedBy: 'productionOrder',
        targetEntity: ProductionOrderFile::class,
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): self
    {
        $number = $number !== null ? trim($number) : null;
        $this->number = $number !== '' ? $number : null;

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

    public function getStatus(): string
    {
        return $this->status;
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

    public function getIssuedAt(): ?\DateTimeImmutable
    {
        return $this->issuedAt;
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
     * @return Collection<int, ProductionOrderFile>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(ProductionOrderFile $file): self
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setProductionOrder($this);
        }

        return $this;
    }

    public function removeFile(ProductionOrderFile $file): self
    {
        if ($this->files->removeElement($file) && $file->getProductionOrder() === $this) {
            $file->setProductionOrder(null);
        }

        return $this;
    }

    public function issue(User $user): self
    {
        $this->status = self::STATUS_IN_WORK;
        $this->issuedBy = $user;
        $this->issuedAt = new \DateTimeImmutable();

        return $this->touch();
    }

    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
