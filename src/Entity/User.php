<?php

namespace App\Entity;

use App\Enum\UserStatusEnum;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[OA\Schema(
    schema: 'User',
    title: 'User',
    description: 'Modèle Utilisateur',
    type: 'object',
    properties: [
        new OA\Property(property: 'id',          type: 'integer', description: 'Identifiant',        example: 1),
        new OA\Property(property: 'email',        type: 'string',  description: 'Email',              example: 'user@example.com'),
        new OA\Property(property: 'firstName',    type: 'string',  description: 'Prénom',             example: 'Mathis',           nullable: true),
        new OA\Property(property: 'lastName',     type: 'string',  description: 'Nom',                example: 'Dauguet',          nullable: true),
        new OA\Property(property: 'status',       type: 'string',  description: 'Statut',             example: 'verified'),
        new OA\Property(property: 'createdAt',    type: 'string',  description: 'Date de création',   example: '2026-03-04 09:31:05'),
        new OA\Property(property: 'updatedAt',    type: 'string',  description: 'Date de mise à jour', example: null,              nullable: true),
        new OA\Property(property: 'lastLoginAt',  type: 'string',  description: 'Dernière connexion', example: null,               nullable: true),
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $id;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read'])]
    private string $email;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: 'string', enumType: UserStatusEnum::class)]
    #[Groups(['user:read'])]
    private UserStatusEnum $status;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['user:read'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $oauthId = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTimeInterface $lastLoginAt = null;

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

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
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
}
