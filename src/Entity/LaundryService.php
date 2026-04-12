<?php

namespace App\Entity;

use App\Repository\LaundryServiceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: LaundryServiceRepository::class)]
class LaundryService
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'laundryServices')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['laundry:read'])]
    private ?Service $service = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'laundryServices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Laundry $laundry = null;

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;
        return $this;
    }

    public function getLaundry(): ?Laundry
    {
        return $this->laundry;
    }

    public function setLaundry(?Laundry $laundry): static
    {
        $this->laundry = $laundry;
        return $this;
    }
}
