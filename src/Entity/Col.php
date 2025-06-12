<?php

namespace App\Entity;

use App\Repository\ColRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert; // Dodaj tę linię

#[ORM\Entity(repositoryClass: ColRepository::class)]
class Col
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Nazwa kolumny nie może być pusta.")] // Dodaj tę linię
    #[Assert\Length(min: 3, max: 255, minMessage: "Nazwa kolumny musi mieć co najmniej {{ limit }} znaki.", maxMessage: "Nazwa kolumny może mieć maksymalnie {{ limit }} znaków.")] // Dodaj tę linię
    private ?string $name = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "Pozycja kolumny nie może być pusta.")] // Dodaj tę linię
    private ?int $position = null;

    #[ORM\ManyToOne(inversedBy: 'cols')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Board $board = null;

    #[ORM\OneToMany(mappedBy: 'col', targetEntity: Task::class, orphanRemoval: true, cascade: ['remove'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $tasks;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function getBoard(): ?Board
    {
        return $this->board;
    }

    public function setBoard(?Board $board): static
    {
        $this->board = $board;
        return $this;
    }

    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setCol($this);
        }
        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getCol() === $this) {
                $task->setCol(null);
            }
        }
        return $this;
    }
}