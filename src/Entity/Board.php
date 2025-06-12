<?php

namespace App\Entity;

use App\Repository\BoardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BoardRepository::class)]
class Board
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Nazwa tablicy nie może być pusta.")]
    #[Assert\Length(min: 3, max: 255, minMessage: "Nazwa tablicy musi mieć co najmniej {{ limit }} znaki.", maxMessage: "Nazwa tablicy może mieć maksymalnie {{ limit }} znaków.")]
    #[Assert\Regex( // Dodano to
        pattern: '/^[a-zA-Z0-9\p{L}\p{P}\s]+$/u', // Dodano to
        message: "Nazwa tablicy może zawierać tylko litery, cyfry, polskie znaki, spacje i znaki interpunkcyjne." // Dodano to
    )] // Dodano to
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'board', targetEntity: Col::class, orphanRemoval: true, cascade: ['remove'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $cols;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    // --- DODANE LINIE DLA WŁAŚCICIELA ---
    #[ORM\ManyToOne(inversedBy: 'boards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null; // Relacja do encji User
    // --- KONIEC DODANYCH LINII ---

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
            // set the owning side to null (unless already changed)
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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
        return $this;
    }
}