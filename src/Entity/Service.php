<?php

namespace App\Entity;

use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: LaundryService::class)]
    private Collection $laundryServices;

    public function __construct()
    {
        $this->laundryServices = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Collection<int, LaundryService>
     */
    public function getLaundryServices(): Collection
    {
        return $this->laundryServices;
    }

    public function addLaundryService(LaundryService $laundryService): static
    {
        if (!$this->laundryServices->contains($laundryService)) {
            $this->laundryServices->add($laundryService);
            $laundryService->setService($this);
        }
        return $this;
    }

    public function removeLaundryService(LaundryService $laundryService): static
    {
        if ($this->laundryServices->removeElement($laundryService)) {
            if ($laundryService->getService() === $this) {
                $laundryService->setService(null);
            }
        }
        return $this;
    }
}
