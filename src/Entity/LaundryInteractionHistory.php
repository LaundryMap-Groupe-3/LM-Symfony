<?php

namespace App\Entity;

use App\Enum\InteractionActionEnum;
use App\Repository\LaundryInteractionHistoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: LaundryInteractionHistoryRepository::class)]
class LaundryInteractionHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['laundry:read'])]
    private int $id;

    #[ORM\ManyToOne(inversedBy: 'laundryInteractionHistories')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['laundry:read'])]
    private Admin $admin;

    #[ORM\ManyToOne(inversedBy: 'laundryInteractionHistories')]
    #[ORM\JoinColumn(nullable: false)]
    private Laundry $laundry;

    #[ORM\Column(type: 'string', enumType: InteractionActionEnum::class)]
    #[Groups(['laundry:read'])]
    private InteractionActionEnum $action;

    #[ORM\Column(length: 255)]
    #[Groups(['laundry:read'])]
    private string $actionReason;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['laundry:read'])]
    private \DateTimeInterface $createdAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAdmin(): Admin
    {
        return $this->admin;
    }

    public function setAdmin(Admin $admin): static
    {
        $this->admin = $admin;
        return $this;
    }

    public function getLaundry(): Laundry
    {
        return $this->laundry;
    }

    public function setLaundry(Laundry $laundry): static
    {
        $this->laundry = $laundry;
        return $this;
    }

    public function getAction(): InteractionActionEnum
    {
        return $this->action;
    }

    public function setAction(InteractionActionEnum $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getActionReason(): string
    {
        return $this->actionReason;
    }

    public function setActionReason(string $actionReason): static
    {
        $this->actionReason = $actionReason;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
