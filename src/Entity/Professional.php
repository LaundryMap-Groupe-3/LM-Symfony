<?php

namespace App\Entity;

use App\Enum\ProfessionalStatusEnum;
use App\Repository\ProfessionalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfessionalRepository::class)]
class Professional
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private string $siret;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'string', enumType: ProfessionalStatusEnum::class)]
    private ProfessionalStatusEnum $status;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $validationDate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rejectionReason = null;

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

    public function getSiret(): string
    {
        return $this->siret;
    }

    public function setSiret(string $siret): static
    {
        $this->siret = $siret;
        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
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

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): static
    {
        $this->rejectionReason = $rejectionReason;
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
