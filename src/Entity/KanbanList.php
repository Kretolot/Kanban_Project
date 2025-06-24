<?php

namespace App\Entity;

use App\Repository\KanbanListRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KanbanListRepository::class)]
class KanbanList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
