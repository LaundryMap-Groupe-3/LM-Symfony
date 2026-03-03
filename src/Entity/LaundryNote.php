<?php

namespace App\Entity;

use App\Repository\LaundryNoteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LaundryNoteRepository::class)]
#[ORM\Table(name: 'laverie_note')]
class LaundryNote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(inversedBy: 'laundryNotes')]
    #[ORM\JoinColumn(nullable: false)]
    private Laundry $laundry;

    #[ORM\ManyToOne(inversedBy: 'laundryNotes')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column]
    private int $rating;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $ratedAt;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $commentedAt = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $response = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $respondedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $commentDeletedReason = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $commentDeletedAt = null;

    #[ORM\OneToMany(mappedBy: 'laundryNote', targetEntity: LaundryNoteReport::class)]
    private Collection $laundryNoteReports;

    public function __construct()
    {
        $this->laundryNoteReports = new ArrayCollection();
    }

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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;
        return $this;
    }

    public function getRatedAt(): \DateTimeInterface
    {
        return $this->ratedAt;
    }

    public function setRatedAt(\DateTimeInterface $ratedAt): static
    {
        $this->ratedAt = $ratedAt;
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

    public function getCommentedAt(): ?\DateTimeInterface
    {
        return $this->commentedAt;
    }

    public function setCommentedAt(?\DateTimeInterface $commentedAt): static
    {
        $this->commentedAt = $commentedAt;
        return $this;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(?string $response): static
    {
        $this->response = $response;
        return $this;
    }

    public function getRespondedAt(): ?\DateTimeInterface
    {
        return $this->respondedAt;
    }

    public function setRespondedAt(?\DateTimeInterface $respondedAt): static
    {
        $this->respondedAt = $respondedAt;
        return $this;
    }

    public function getCommentDeletedReason(): ?string
    {
        return $this->commentDeletedReason;
    }

    public function setCommentDeletedReason(?string $commentDeletedReason): static
    {
        $this->commentDeletedReason = $commentDeletedReason;
        return $this;
    }

    public function getCommentDeletedAt(): ?\DateTimeInterface
    {
        return $this->commentDeletedAt;
    }

    public function setCommentDeletedAt(?\DateTimeInterface $commentDeletedAt): static
    {
        $this->commentDeletedAt = $commentDeletedAt;
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
            $laundryNoteReport->setLaundryNote($this);
        }
        return $this;
    }
}
