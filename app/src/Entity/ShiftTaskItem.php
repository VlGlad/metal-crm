<?php

namespace App\Entity;

use App\Repository\ShiftTaskItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShiftTaskItemRepository::class)]
#[ORM\Table(name: 'shift_task_items')]
class ShiftTaskItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ShiftTaskSection $section = null;

    #[ORM\Column(type: 'text')]
    private string $mark = '';

    #[ORM\Column(nullable: true)]
    private ?int $firstShiftPlan = null;

    #[ORM\Column(nullable: true)]
    private ?int $firstShiftFact = null;

    #[ORM\Column(nullable: true)]
    private ?int $secondShiftPlan = null;

    #[ORM\Column(nullable: true)]
    private ?int $secondShiftFact = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    #[ORM\Column]
    private int $sortOrder = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSection(): ?ShiftTaskSection
    {
        return $this->section;
    }

    public function setSection(?ShiftTaskSection $section): self
    {
        $this->section = $section;
        return $this;
    }

    public function getMark(): string
    {
        return $this->mark;
    }

    public function setMark(string $mark): self
    {
        $this->mark = $mark;
        return $this;
    }

    public function getFirstShiftPlan(): ?int
    {
        return $this->firstShiftPlan;
    }

    public function setFirstShiftPlan(?int $firstShiftPlan): self
    {
        $this->firstShiftPlan = $firstShiftPlan;
        return $this;
    }

    public function getFirstShiftFact(): ?int
    {
        return $this->firstShiftFact;
    }

    public function setFirstShiftFact(?int $firstShiftFact): self
    {
        $this->firstShiftFact = $firstShiftFact;
        return $this;
    }

    public function getSecondShiftPlan(): ?int
    {
        return $this->secondShiftPlan;
    }

    public function setSecondShiftPlan(?int $secondShiftPlan): self
    {
        $this->secondShiftPlan = $secondShiftPlan;
        return $this;
    }

    public function getSecondShiftFact(): ?int
    {
        return $this->secondShiftFact;
    }

    public function setSecondShiftFact(?int $secondShiftFact): self
    {
        $this->secondShiftFact = $secondShiftFact;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }
}
