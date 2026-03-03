<?php

namespace App\Entity;

use App\Repository\PaymentMethodRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentMethodRepository::class)]
#[ORM\Table(name: 'methode_paiement')]
class PaymentMethod
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\OneToMany(mappedBy: 'paymentMethod', targetEntity: LaundryPayment::class)]
    private Collection $laundryPayments;

    public function __construct()
    {
        $this->laundryPayments = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
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
            $laundryPayment->setPaymentMethod($this);
        }
        return $this;
    }

    public function removeLaundryPayment(LaundryPayment $laundryPayment): static
    {
        if ($this->laundryPayments->removeElement($laundryPayment)) {
            if ($laundryPayment->getPaymentMethod() === $this) {
                $laundryPayment->setPaymentMethod(null);
            }
        }
        return $this;
    }
}
