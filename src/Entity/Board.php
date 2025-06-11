<?php

namespace App\Entity;

use App\Repository\BoardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BoardRepository::class)]
class Board
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'board', targetEntity: Col::class, orphanRemoval: true, cascade: ['remove'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $cols;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->cols = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
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

    /**
     * @return Collection<int, Col>
     */
    public function getCols(): Collection
    {
        return $this->cols;
    }

    public function addCol(Col $col): static
    {
        if (!$this->cols->contains($col)) {
            $this->cols->add($col);
            $col->setBoard($this);
        }
        return $this;
    }

    public function removeCol(Col $col): static
    {
        if ($this->cols->removeElement($col)) {
            if ($col->getBoard() === $this) {
                $col->setBoard(null);
            }
        }
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}