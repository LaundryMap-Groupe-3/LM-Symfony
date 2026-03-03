<?php

namespace App\Entity;

use App\Enum\DayOfWeekEnum;
use App\Repository\LaundryClosureRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LaundryClosureRepository::class)]
class LaundryClosure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(inversedBy: 'laundryClosures')]
    #[ORM\JoinColumn(nullable: false)]
    private Laundry $laundry;

    #[ORM\Column(type: 'string', enumType: DayOfWeekEnum::class)]
    private DayOfWeekEnum $day;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(type: 'time')]
    private \DateTimeInterface $startTime;

    #[ORM\Column(type: 'time')]
    private \DateTimeInterface $endTime;

    public function getId(): int
    {
        return $this->id;
    }

    public function getLaundry(): Laundry
    {
        return $this->laundry;
    }

    public function setLaundry(Laundry $laundry): static
    {
        $this->laundry = $laundry;
        return $this;
    }

    public function getDay(): DayOfWeekEnum
    {
        return $this->day;
    }

    public function setDay(DayOfWeekEnum $day): static
    {
        $this->day = $day;
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

    public function getStartTime(): \DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): \DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;
        return $this;
    }
}
