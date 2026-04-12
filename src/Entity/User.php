<?php

namespace App\Entity;

use App\Enum\UserStatusEnum;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $email;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['laundry:read'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['laundry:read'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: 'string', enumType: UserStatusEnum::class)]
    private UserStatusEnum $status;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $oauthId = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $emailVerifiedAt = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Professional $professional = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?UserPreference $userPreference = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: LaundryFavorite::class)]
    private Collection $laundryFavorites;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: LaundryNote::class)]
    private Collection $laundryNotes;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: LaundryNoteReport::class)]
    private Collection $laundryNoteReports;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserInteractionHistory::class)]
    private Collection $userInteractionHistories;

    public function __construct()
    {
        $this->laundryFavorites = new ArrayCollection();
        $this->laundryNotes = new ArrayCollection();
        $this->laundryNoteReports = new ArrayCollection();
        $this->userInteractionHistories = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getStatus(): UserStatusEnum
    {
        return $this->status;
    }

    public function setStatus(UserStatusEnum $status): static
    {
        $this->status = $status;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getOauthId(): ?string
    {
        return $this->oauthId;
    }

    public function setOauthId(?string $oauthId): static
    {
        $this->oauthId = $oauthId;
        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function getEmailVerifiedAt(): ?\DateTimeInterface
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?\DateTimeInterface $emailVerifiedAt): static
    {
        $this->emailVerifiedAt = $emailVerifiedAt;
        return $this;
    }

    public function getProfessional(): ?Professional
    {
        return $this->professional;
    }

    public function setProfessional(?Professional $professional): static
    {
        if ($professional !== null && $professional->getUser() !== $this) {
            $professional->setUser($this);
        }
        $this->professional = $professional;
        return $this;
    }

    public function getUserPreference(): ?UserPreference
    {
        return $this->userPreference;
    }

    public function setUserPreference(?UserPreference $userPreference): static
    {
        if ($userPreference !== null && $userPreference->getUser() !== $this) {
            $userPreference->setUser($this);
        }
        $this->userPreference = $userPreference;
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
            $laundryFavorite->setUser($this);
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
            $laundryNote->setUser($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, LaundryNoteReport>
     */
    public function getLaundryNoteReports(): Collection
    {
        return $this->laundryNoteReports;
    }

    public function addLaundryNoteReport(LaundryNoteReport $laundryNoteReport): static
    {
        if (!$this->laundryNoteReports->contains($laundryNoteReport)) {
            $this->laundryNoteReports->add($laundryNoteReport);
            $laundryNoteReport->setUser($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, UserInteractionHistory>
     */
    public function getUserInteractionHistories(): Collection
    {
        return $this->userInteractionHistories;
    }

    public function addUserInteractionHistory(UserInteractionHistory $userInteractionHistory): static
    {
        if (!$this->userInteractionHistories->contains($userInteractionHistory)) {
            $this->userInteractionHistories->add($userInteractionHistory);
            $userInteractionHistory->setUser($this);
        }
        return $this;
    }

    public function removeUserInteractionHistory(UserInteractionHistory $userInteractionHistory): static
    {
        if ($this->userInteractionHistories->removeElement($userInteractionHistory)) {
            if ($userInteractionHistory->getUser() === $this) {
                $userInteractionHistory->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @see UserInterface
     * Determine roles dynamically based on relationships
     */
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        
        // If user has a professional relationship, add ROLE_PROFESSIONAL
        if ($this->professional !== null) {
            $roles[] = 'ROLE_PROFESSIONAL';
        }
        
        return $roles;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }
}
