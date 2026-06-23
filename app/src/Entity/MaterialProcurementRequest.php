<?php

namespace App\Entity;

use App\Repository\MaterialProcurementRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MaterialProcurementRequestRepository::class)]
#[ORM\Table(name: 'material_procurement_requests')]
class MaterialProcurementRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $month;

    /**
     * @var Collection<int, ProductionOrder>
     */
    #[ORM\ManyToMany(targetEntity: ProductionOrder::class)]
    #[ORM\JoinTable(name: 'material_procurement_request_orders')]
    #[ORM\JoinColumn(name: 'request_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'production_order_id', referencedColumnName: 'id', onDelete: 'RESTRICT')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $orders;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, MaterialProcurementRequestFile>
     */
    #[ORM\OneToMany(
        mappedBy: 'request',
        targetEntity: MaterialProcurementRequestFile::class,
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
        $this->orders = new ArrayCollection();
        $this->files = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getMonth(): \DateTimeImmutable { return $this->month; }

    public function setMonth(\DateTimeImmutable $month): self
    {
        $this->month = $month->modify('first day of this month')->setTime(0, 0);
        return $this;
    }

    /** @return Collection<int, ProductionOrder> */
    public function getOrders(): Collection { return $this->orders; }

    public function replaceOrders(iterable $orders): self
    {
        $this->orders->clear();

        foreach ($orders as $order) {
            if ($order instanceof ProductionOrder && !$this->orders->contains($order)) {
                $this->orders->add($order);
            }
        }

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

    /** @return Collection<int, MaterialProcurementRequestFile> */
    public function getFiles(): Collection { return $this->files; }

    public function addFile(MaterialProcurementRequestFile $file): self
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setRequest($this);
        }

        return $this;
    }

    public function removeFile(MaterialProcurementRequestFile $file): self
    {
        if ($this->files->removeElement($file) && $file->getRequest() === $this) {
            $file->setRequest(null);
        }

        return $this;
    }

    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
}
