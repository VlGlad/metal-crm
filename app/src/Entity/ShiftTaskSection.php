<?php

namespace App\Entity;

use App\Repository\ShiftTaskSectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShiftTaskSectionRepository::class)]
#[ORM\Table(name: 'shift_task_sections')]
class ShiftTaskSection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sections')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ShiftTask $shiftTask = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\OneToMany(
        mappedBy: 'section',
        targetEntity: ShiftTaskItem::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'id' => 'ASC'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShiftTask(): ?ShiftTask
    {
        return $this->shiftTask;
    }

    public function setShiftTask(?ShiftTask $shiftTask): self
    {
        $this->shiftTask = $shiftTask;
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

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    /**
     * @return Collection<int, ShiftTaskItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(ShiftTaskItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setSection($this);
        }

        return $this;
    }

    public function removeItem(ShiftTaskItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getSection() === $this) {
                $item->setSection(null);
            }
        }

        return $this;
    }
}
