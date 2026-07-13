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
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_PURCHASED = 'purchased';
    public const STATUS_RECEIVED = 'received';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $month;

    #[ORM\Column(length: 30)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $submittedBy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $acceptedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $acceptedBy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $purchasedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $purchasedBy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $receivedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $receivedBy = null;

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

    /**
     * @var Collection<int, MaterialProcurementRequestEvent>
     */
    #[ORM\OneToMany(
        mappedBy: 'request',
        targetEntity: MaterialProcurementRequestEvent::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['createdAt' => 'DESC', 'id' => 'DESC'])]
    private Collection $events;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->month = $now->modify('first day of this month')->setTime(0, 0);
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->orders = new ArrayCollection();
        $this->files = new ArrayCollection();
        $this->events = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getMonth(): \DateTimeImmutable { return $this->month; }
    public function getStatus(): string { return $this->status; }

    public function getSubmittedAt(): ?\DateTimeImmutable { return $this->submittedAt; }
    public function getSubmittedBy(): ?User { return $this->submittedBy; }
    public function getAcceptedAt(): ?\DateTimeImmutable { return $this->acceptedAt; }
    public function getAcceptedBy(): ?User { return $this->acceptedBy; }
    public function getPurchasedAt(): ?\DateTimeImmutable { return $this->purchasedAt; }
    public function getPurchasedBy(): ?User { return $this->purchasedBy; }
    public function getReceivedAt(): ?\DateTimeImmutable { return $this->receivedAt; }
    public function getReceivedBy(): ?User { return $this->receivedBy; }

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

    /** @return Collection<int, MaterialProcurementRequestEvent> */
    public function getEvents(): Collection { return $this->events; }

    public function addEvent(MaterialProcurementRequestEvent $event): self
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setRequest($this);
        }

        return $this;
    }

    public function transitionTo(string $status, ?User $user, ?string $comment = null): self
    {
        $fromStatus = $this->status;
        $now = new \DateTimeImmutable();
        $this->status = $status;

        match ($status) {
            self::STATUS_SUBMITTED => $this->markSubmitted($now, $user),
            self::STATUS_ACCEPTED => $this->markAccepted($now, $user),
            self::STATUS_PURCHASED => $this->markPurchased($now, $user),
            self::STATUS_RECEIVED => $this->markReceived($now, $user),
            default => null,
        };

        $this->addEvent((new MaterialProcurementRequestEvent())
            ->setFromStatus($fromStatus)
            ->setToStatus($status)
            ->setComment($comment)
            ->setCreatedBy($user));

        return $this->touch();
    }

    private function markSubmitted(\DateTimeImmutable $date, ?User $user): void
    {
        $this->submittedAt = $date;
        $this->submittedBy = $user;
    }

    private function markAccepted(\DateTimeImmutable $date, ?User $user): void
    {
        $this->acceptedAt = $date;
        $this->acceptedBy = $user;
    }

    private function markPurchased(\DateTimeImmutable $date, ?User $user): void
    {
        $this->purchasedAt = $date;
        $this->purchasedBy = $user;
    }

    private function markReceived(\DateTimeImmutable $date, ?User $user): void
    {
        $this->receivedAt = $date;
        $this->receivedBy = $user;
    }

    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
}

