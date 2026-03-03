<?php

namespace App\Entity;

use App\Repository\LaundryMediaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LaundryMediaRepository::class)]
#[ORM\Table(name: 'laverie_media')]
class LaundryMedia
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'laundryMedias')]
    #[ORM\JoinColumn(nullable: false)]
    private Laundry $laundry;

    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Media $media;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    public function getLaundry(): Laundry
    {
        return $this->laundry;
    }

    public function setLaundry(Laundry $laundry): static
    {
        $this->laundry = $laundry;
        return $this;
    }

    public function getMedia(): Media
    {
        return $this->media;
    }

    public function setMedia(Media $media): static
    {
        $this->media = $media;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }
}
