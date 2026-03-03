<?php

namespace App\Entity;

use App\Repository\LaundryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LaundryRepository::class)]
#[ORM\Table(name: 'laverie')]
class Laundry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(inversedBy: 'laundries')]
    #[ORM\JoinColumn(nullable: false)]
    private Professional $professional;

    // Voir pour l'enum
    #[ORM\Column(length: 255)]
    private string $status;

    #[ORM\Column(nullable: true)]
    private ?int $wiLineReference = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Address $address;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Media $logo = null;

    #[ORM\Column(length: 255)]
    private string $establishmentName;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\OneToMany(mappedBy: 'laundry', targetEntity: LaundryFavorite::class)]
    private Collection $laundryFavorites;

    #[ORM\OneToMany(mappedBy: 'laundry', targetEntity: LaundryEquipment::class)]
    private Collection $laundryEquipments;

    #[ORM\OneToMany(mappedBy: 'laundry', targetEntity: LaundryMedia::class)]
    private Collection $laundryMedias;

    #[ORM\OneToMany(mappedBy: 'laundry', targetEntity: LaundryNote::class)]
    private Collection $laundryNotes;

    #[ORM\OneToMany(mappedBy: 'laundry', targetEntity: LaundryService::class)]
    private Collection $laundryServices;

    #[ORM\OneToMany(mappedBy: 'laundry', targetEntity: LaundryClosure::class)]
    private Collection $laundryClosures;

    #[ORM\OneToMany(mappedBy: 'laundry', targetEntity: LaundryExceptionalClosure::class)]
    private Collection $laundryExceptionalClosures;

    #[ORM\OneToMany(mappedBy: 'laundry', targetEntity: LaundryInteractionHistory::class)]
    private Collection $laundryInteractionHistories;

    #[ORM\OneToMany(mappedBy: 'laundry', targetEntity: LaundryPayment::class)]
    private Collection $laundryPayments;

    public function __construct()
    {
        $this->laundryFavorites = new ArrayCollection();
        $this->laundryEquipments = new ArrayCollection();
        $this->laundryMedias = new ArrayCollection();
        $this->laundryNotes = new ArrayCollection();
        $this->laundryServices = new ArrayCollection();
        $this->laundryClosures = new ArrayCollection();
        $this->laundryExceptionalClosures = new ArrayCollection();
        $this->laundryInteractionHistories = new ArrayCollection();
        $this->laundryPayments = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getProfessional(): Professional
    {
        return $this->professional;
    }

    public function setProfessional(Professional $professional): static
    {
        $this->professional = $professional;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getWiLineReference(): ?int
    {
        return $this->wiLineReference;
    }

    public function setWiLineReference(?int $wiLineReference): static
    {
        $this->wiLineReference = $wiLineReference;
        return $this;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getLogo(): ?Media
    {
        return $this->logo;
    }

    public function setLogo(?Media $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    public function getEstablishmentName(): string
    {
        return $this->establishmentName;
    }

    public function setEstablishmentName(string $establishmentName): static
    {
        $this->establishmentName = $establishmentName;
        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
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

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    /**
     * @return Collection<int, LaundryFavorite>
     */
    public function getLaundryFavorites(): Collection
    {
        return $this->laundryFavorites;
    }

    public function addLaundryFavorite(LaundryFavorite $laundryFavorite): static
    {
        if (!$this->laundryFavorites->contains($laundryFavorite)) {
            $this->laundryFavorites->add($laundryFavorite);
            $laundryFavorite->setLaundry($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, LaundryEquipment>
     */
    public function getLaundryEquipments(): Collection
    {
        return $this->laundryEquipments;
    }

    public function addLaundryEquipment(LaundryEquipment $laundryEquipment): static
    {
        if (!$this->laundryEquipments->contains($laundryEquipment)) {
            $this->laundryEquipments->add($laundryEquipment);
            $laundryEquipment->setLaundry($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, LaundryMedia>
     */
    public function getLaundryMedias(): Collection
    {
        return $this->laundryMedias;
    }

    public function addLaundryMedia(LaundryMedia $laundryMedia): static
    {
        if (!$this->laundryMedias->contains($laundryMedia)) {
            $this->laundryMedias->add($laundryMedia);
            $laundryMedia->setLaundry($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, LaundryNote>
     */
    public function getLaundryNotes(): Collection
    {
        return $this->laundryNotes;
    }

    public function addLaundryNote(LaundryNote $laundryNote): static
    {
        if (!$this->laundryNotes->contains($laundryNote)) {
            $this->laundryNotes->add($laundryNote);
            $laundryNote->setLaundry($this);
        }
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
            $laundryService->setLaundry($this);
        }
        return $this;
    }

    public function removeLaundryService(LaundryService $laundryService): static
    {
        if ($this->laundryServices->removeElement($laundryService)) {
            if ($laundryService->getLaundry() === $this) {
                $laundryService->setLaundry(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, LaundryClosure>
     */
    public function getLaundryClosures(): Collection
    {
        return $this->laundryClosures;
    }

    public function addLaundryClosure(LaundryClosure $laundryClosure): static
    {
        if (!$this->laundryClosures->contains($laundryClosure)) {
            $this->laundryClosures->add($laundryClosure);
            $laundryClosure->setLaundry($this);
        }
        return $this;
    }

    public function removeLaundryClosure(LaundryClosure $laundryClosure): static
    {
        $this->laundryClosures->removeElement($laundryClosure);
        return $this;
    }

    /**
     * @return Collection<int, LaundryExceptionalClosure>
     */
    public function getLaundryExceptionalClosures(): Collection
    {
        return $this->laundryExceptionalClosures;
    }

    public function addLaundryExceptionalClosure(LaundryExceptionalClosure $laundryExceptionalClosure): static
    {
        if (!$this->laundryExceptionalClosures->contains($laundryExceptionalClosure)) {
            $this->laundryExceptionalClosures->add($laundryExceptionalClosure);
            $laundryExceptionalClosure->setLaundry($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, LaundryInteractionHistory>
     */
    public function getLaundryInteractionHistories(): Collection
    {
        return $this->laundryInteractionHistories;
    }

    public function addLaundryInteractionHistory(LaundryInteractionHistory $laundryInteractionHistory): static
    {
        if (!$this->laundryInteractionHistories->contains($laundryInteractionHistory)) {
            $this->laundryInteractionHistories->add($laundryInteractionHistory);
            $laundryInteractionHistory->setLaundry($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, LaundryPayment>
     */
    public function getLaundryPayments(): Collection
    {
        return $this->laundryPayments;
    }

    public function addLaundryPayment(LaundryPayment $laundryPayment): static
    {
        if (!$this->laundryPayments->contains($laundryPayment)) {
            $this->laundryPayments->add($laundryPayment);
            $laundryPayment->setLaundry($this);
        }
        return $this;
    }

    public function removeLaundryPayment(LaundryPayment $laundryPayment): static
    {
        if ($this->laundryPayments->removeElement($laundryPayment)) {
            if ($laundryPayment->getLaundry() === $this) {
                $laundryPayment->setLaundry(null);
            }
        }
        return $this;
    }
}
