<?php

namespace App\Entity;

use App\Enum\LaundryNoteReportReasonEnum;
use App\Repository\LaundryNoteReportRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LaundryNoteReportRepository::class)]
class LaundryNoteReport
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'laundryNoteReports')]
    #[ORM\JoinColumn(nullable: false)]
    private LaundryNote $laundryNote;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'laundryNoteReports')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'string', enumType: LaundryNoteReportReasonEnum::class)]
    private LaundryNoteReportReasonEnum $reason;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    public function getLaundryNote(): LaundryNote
    {
        return $this->laundryNote;
    }

    public function setLaundryNote(LaundryNote $laundryNote): static
    {
        $this->laundryNote = $laundryNote;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
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

    public function getReason(): LaundryNoteReportReasonEnum
    {
        return $this->reason;
    }

    public function setReason(LaundryNoteReportReasonEnum $reason): static
    {
        $this->reason = $reason;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }
}
