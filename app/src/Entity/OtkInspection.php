<?php

namespace App\Entity;

use App\Repository\OtkInspectionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OtkInspectionRepository::class)]
#[ORM\Table(name: 'otk_inspections')]
class OtkInspection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'date_immutable')]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(length: 255)]
    private string $project = '';

    #[ORM\Column]
    private int $presentedQuantity = 0;

    #[ORM\Column]
    private int $acceptedQuantity = 0;

    #[ORM\Column]
    private int $rejectedQuantity = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $nonconformityDescription = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nonconformityActNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $executorName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $controllerName = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $executorSignedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $controllerSignedAt = null;

    #[ORM\Column(length: 30)]
    private string $status = 'draft';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->date = $now;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function touch(): void
    {
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getProject(): string
    {
        return $this->project;
    }

    public function setProject(string $project): self
    {
        $this->project = $project;
        return $this;
    }

    public function getPresentedQuantity(): int
    {
        return $this->presentedQuantity;
    }

    public function setPresentedQuantity(int $presentedQuantity): self
    {
        $this->presentedQuantity = $presentedQuantity;
        return $this;
    }

    public function getAcceptedQuantity(): int
    {
        return $this->acceptedQuantity;
    }

    public function setAcceptedQuantity(int $acceptedQuantity): self
    {
        $this->acceptedQuantity = $acceptedQuantity;
        return $this;
    }

    public function getRejectedQuantity(): int
    {
        return $this->rejectedQuantity;
    }

    public function setRejectedQuantity(int $rejectedQuantity): self
    {
        $this->rejectedQuantity = $rejectedQuantity;
        return $this;
    }

    public function getNonconformityDescription(): ?string
    {
        return $this->nonconformityDescription;
    }

    public function setNonconformityDescription(?string $nonconformityDescription): self
    {
        $this->nonconformityDescription = $nonconformityDescription;
        return $this;
    }

    public function getNonconformityActNumber(): ?string
    {
        return $this->nonconformityActNumber;
    }

    public function setNonconformityActNumber(?string $nonconformityActNumber): self
    {
        $this->nonconformityActNumber = $nonconformityActNumber;
        return $this;
    }

    public function getExecutorName(): ?string
    {
        return $this->executorName;
    }

    public function setExecutorName(?string $executorName): self
    {
        $this->executorName = $executorName;
        return $this;
    }

    public function getControllerName(): ?string
    {
        return $this->controllerName;
    }

    public function setControllerName(?string $controllerName): self
    {
        $this->controllerName = $controllerName;
        return $this;
    }

    public function getExecutorSignedAt(): ?\DateTimeImmutable
    {
        return $this->executorSignedAt;
    }

    public function setExecutorSignedAt(?\DateTimeImmutable $executorSignedAt): self
    {
        $this->executorSignedAt = $executorSignedAt;
        return $this;
    }

    public function getControllerSignedAt(): ?\DateTimeImmutable
    {
        return $this->controllerSignedAt;
    }

    public function setControllerSignedAt(?\DateTimeImmutable $controllerSignedAt): self
    {
        $this->controllerSignedAt = $controllerSignedAt;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
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
}