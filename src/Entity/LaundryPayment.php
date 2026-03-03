<?php

namespace App\Entity;

use App\Repository\LaundryPaymentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LaundryPaymentRepository::class)]
class LaundryPayment
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'laundryPayments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PaymentMethod $paymentMethod = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'laundryPayments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Laundry $laundry = null;

    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?PaymentMethod $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getLaundry(): ?Laundry
    {
        return $this->laundry;
    }

    public function setLaundry(?Laundry $laundry): static
    {
        $this->laundry = $laundry;
        return $this;
    }
}
