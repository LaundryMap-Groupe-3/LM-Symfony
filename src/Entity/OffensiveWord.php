<?php

namespace App\Entity;

use App\Repository\OffensiveWordRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: OffensiveWordRepository::class)]
class OffensiveWord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['admin:offensive_word'])]
    private int $id;

    #[ORM\Column(length: 255)]
    #[Groups(['admin:offensive_word'])]
    private string $label;

    public function getId(): int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }
}
