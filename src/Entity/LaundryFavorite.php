<?php

namespace App\Entity;

use App\Repository\LaundryFavoriteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: LaundryFavoriteRepository::class)]
class LaundryFavorite
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'laundryFavorites')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['favorite-laundry:read'])]
    private Laundry $laundry;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'laundryFavorites')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['laundry:read'])]
    private User $user;

    public function getLaundry(): Laundry
    {
        return $this->laundry;
    }

    public function setLaundry(Laundry $laundry): static
    {
        $this->laundry = $laundry;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }
}
