<?php

namespace App\Entity;

use App\Repository\AdminRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: AdminRepository::class)]
class Admin implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $email;

    #[ORM\Column(length: 255)]
    private string $password;

    #[ORM\OneToMany(mappedBy: 'admin', targetEntity: UserInteractionHistory::class)]
    private Collection $userInteractionHistories;

    #[ORM\OneToMany(mappedBy: 'admin', targetEntity: ProfessionalInteractionHistory::class)]
    private Collection $professionalInteractionHistories;

    #[ORM\OneToMany(mappedBy: 'admin', targetEntity: LaundryInteractionHistory::class)]
    private Collection $laundryInteractionHistories;

    #[ORM\OneToOne(mappedBy: 'admin', cascade: ['persist', 'remove'])]
    private ?AdminPreference $adminPreference = null;

    public function getAdminPreference(): ?AdminPreference
    {
        return $this->adminPreference;
    }

    public function setAdminPreference(?AdminPreference $adminPreference): static
    {
        if ($adminPreference !== null && $adminPreference->getAdmin() !== $this) {
            $adminPreference->setAdmin($this);
        }
        $this->adminPreference = $adminPreference;
        return $this;
    }

    public function __construct()
    {
        $this->userInteractionHistories = new ArrayCollection();
        $this->professionalInteractionHistories = new ArrayCollection();
        $this->laundryInteractionHistories = new ArrayCollection();
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

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
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
            $userInteractionHistory->setAdmin($this);
        }
        return $this;
    }

    public function removeUserInteractionHistory(UserInteractionHistory $userInteractionHistory): static
    {
        if ($this->userInteractionHistories->removeElement($userInteractionHistory)) {
            if ($userInteractionHistory->getAdmin() === $this) {
                $userInteractionHistory->setAdmin(null);
            }
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
            $professionalInteractionHistory->setAdmin($this);
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
            $laundryInteractionHistory->setAdmin($this);
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
     * Admins always have ROLE_ADMIN
     */
    public function getRoles(): array
    {
        return ['ROLE_ADMIN', 'ROLE_USER'];
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }
}
