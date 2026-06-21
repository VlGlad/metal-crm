<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Enum\DocumentStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ProductionDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: DocumentType::class)]
    private DocumentType $type;

    #[ORM\Column(enumType: DocumentStatus::class)]
    private DocumentStatus $status;

    #[ORM\ManyToOne]
    private ProductionOrder $productionOrder;

    #[ORM\ManyToOne]
    private ?User $createdBy = null;

    #[ORM\ManyToOne]
    private ?User $approvedBy = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $payload = [];

    public function getType(): DocumentType
    {
        return $this->type;
    }

    public function getStatus(): DocumentStatus
    {
        return $this->status;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }
}
