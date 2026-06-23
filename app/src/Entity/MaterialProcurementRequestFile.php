<?php

namespace App\Entity;

use App\Enum\DocumentType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'material_procurement_request_files')]
class MaterialProcurementRequestFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MaterialProcurementRequest::class, inversedBy: 'files')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?MaterialProcurementRequest $request = null;

    #[ORM\Column(enumType: DocumentType::class)]
    private DocumentType $documentType;

    #[ORM\Column(length: 255)]
    private string $originalName;

    #[ORM\Column(length: 255, unique: true)]
    private string $storedName;

    #[ORM\Column(length: 150)]
    private string $mimeType;

    #[ORM\Column]
    private int $size;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $uploadedBy = null;

    #[ORM\Column]
    private \DateTimeImmutable $uploadedAt;

    public function __construct()
    {
        $this->uploadedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getRequest(): ?MaterialProcurementRequest { return $this->request; }

    public function setRequest(?MaterialProcurementRequest $request): self
    {
        $this->request = $request;
        return $this;
    }

    public function getDocumentType(): DocumentType { return $this->documentType; }

    public function setDocumentType(DocumentType $documentType): self
    {
        $this->documentType = $documentType;
        return $this;
    }

    public function getOriginalName(): string { return $this->originalName; }

    public function setOriginalName(string $originalName): self
    {
        $this->originalName = $originalName;
        return $this;
    }

    public function getStoredName(): string { return $this->storedName; }

    public function setStoredName(string $storedName): self
    {
        $this->storedName = $storedName;
        return $this;
    }

    public function getMimeType(): string { return $this->mimeType; }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getSize(): int { return $this->size; }

    public function setSize(int $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function getUploadedBy(): ?User { return $this->uploadedBy; }

    public function setUploadedBy(?User $uploadedBy): self
    {
        $this->uploadedBy = $uploadedBy;
        return $this;
    }

    public function getUploadedAt(): \DateTimeImmutable { return $this->uploadedAt; }
}
