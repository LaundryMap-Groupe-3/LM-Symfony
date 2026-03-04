<?php

namespace App\Entity;

use App\Enum\ThemeEnum;
use App\Repository\UserPreferenceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserPreferenceRepository::class)]
class UserPreference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(inversedBy: 'userPreferences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Language $language = null;

    #[ORM\Column(type: 'string', enumType: ThemeEnum::class, nullable: true)]
    private ?ThemeEnum $theme = null;

    #[ORM\Column]
    private bool $notifications = false;

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

    public function getLanguage(): ?Language
    {
        return $this->language;
    }

    public function setLanguage(?Language $language): static
    {
        $this->language = $language;
        return $this;
    }

    public function getTheme(): ?ThemeEnum
    {
        return $this->theme;
    }

    public function setTheme(?ThemeEnum $theme): static
    {
        $this->theme = $theme;
        return $this;
    }

    public function isNotifications(): bool
    {
        return $this->notifications;
    }

    public function setNotifications(bool $notifications): static
    {
        $this->notifications = $notifications;
        return $this;
    }
}
