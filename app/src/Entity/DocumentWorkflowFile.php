<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'document_workflow_files')]
class DocumentWorkflowFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DocumentWorkflow::class, inversedBy: 'files')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DocumentWorkflow $workflow = null;

    #[ORM\Column(length: 255)] private string $originalName = '';
    #[ORM\Column(length: 255, unique: true)] private string $storedName = '';
    #[ORM\Column(length: 150)] private string $mimeType = '';
    #[ORM\Column] private int $size = 0;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $uploadedBy = null;
    #[ORM\Column] private \DateTimeImmutable $uploadedAt;

    public function __construct() { $this->uploadedAt = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getWorkflow(): ?DocumentWorkflow { return $this->workflow; }
    public function setWorkflow(?DocumentWorkflow $workflow): self { $this->workflow = $workflow; return $this; }
    public function getOriginalName(): string { return $this->originalName; }
    public function setOriginalName(string $originalName): self { $this->originalName = $originalName; return $this; }
    public function getStoredName(): string { return $this->storedName; }
    public function setStoredName(string $storedName): self { $this->storedName = $storedName; return $this; }
    public function getMimeType(): string { return $this->mimeType; }
    public function setMimeType(string $mimeType): self { $this->mimeType = $mimeType; return $this; }
    public function getSize(): int { return $this->size; }
    public function setSize(int $size): self { $this->size = $size; return $this; }
    public function getUploadedBy(): ?User { return $this->uploadedBy; }
    public function setUploadedBy(?User $uploadedBy): self { $this->uploadedBy = $uploadedBy; return $this; }
    public function getUploadedAt(): \DateTimeImmutable { return $this->uploadedAt; }
}