<?php

namespace App\Entity;

use App\Enum\ProfessionalStatusEnum;
use App\Repository\ProfessionalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: ProfessionalRepository::class)]
class Professional implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private int $siren;

    #[ORM\Column(type: 'string', enumType: ProfessionalStatusEnum::class)]
    private ProfessionalStatusEnum $status;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $validationDate = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Address $address = null;

    #[ORM\OneToMany(mappedBy: 'professional', targetEntity: Laundry::class)]
    private Collection $laundries;

    #[ORM\OneToMany(mappedBy: 'professional', targetEntity: ProfessionalInteractionHistory::class)]
    private Collection $professionalInteractionHistories;

    public function __construct()
    {
        $this->laundries = new ArrayCollection();
        $this->professionalInteractionHistories = new ArrayCollection();
    }

    public function getUserIdentifier(): string
    {
        return $this->user->getUserIdentifier();
    }

    public function getRoles(): array {
        return ['ROLE_PROFESSIONAL'];
    }
    
    public function eraseCredentials(): void
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getSiren(): int
    {
        return $this->siren;
    }

    public function setSiren(int $siren): static
    {
        $this->siren = $siren;
        return $this;
    }

    public function getStatus(): ProfessionalStatusEnum
    {
        return $this->status;
    }

    public function setStatus(ProfessionalStatusEnum $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getValidationDate(): ?\DateTimeInterface
    {
        return $this->validationDate;
    }

    public function setValidationDate(?\DateTimeInterface $validationDate): static
    {
        $this->validationDate = $validationDate;
        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): static
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return Collection<int, Laundry>
     */
    public function getLaundries(): Collection
    {
        return $this->laundries;
    }

    public function addLaundry(Laundry $laundry): static
    {
        if (!$this->laundries->contains($laundry)) {
            $this->laundries->add($laundry);
            $laundry->setProfessional($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, ProfessionalInteractionHistory>
     */
    public function getProfessionalInteractionHistories(): Collection
    {
        return $this->professionalInteractionHistories;
    }

    public function addProfessionalInteractionHistory(ProfessionalInteractionHistory $professionalInteractionHistory): static
    {
        if (!$this->professionalInteractionHistories->contains($professionalInteractionHistory)) {
            $this->professionalInteractionHistories->add($professionalInteractionHistory);
            $professionalInteractionHistory->setProfessional($this);
        }
        return $this;
    }
}
