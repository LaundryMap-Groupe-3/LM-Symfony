<?php

namespace App\Entity;

use App\Enum\LaundryEquipmentTypeEnum;
use App\Repository\LaundryEquipmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: LaundryEquipmentRepository::class)]
class LaundryEquipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['laundry:read'])]
    private int $id;

    #[ORM\ManyToOne(inversedBy: 'laundryEquipments')]
    #[ORM\JoinColumn(nullable: false)]
    private Laundry $laundry;

    #[ORM\Column(nullable: true)]
    #[Groups(['laundry:read'])]
    private ?int $equipmentReference = null;

    #[ORM\Column(length: 255)]
    #[Groups(['laundry:read'])]
    private string $name;


    #[ORM\Column(type: 'string', enumType: LaundryEquipmentTypeEnum::class)]
    #[Groups(['laundry:read'])]
    private LaundryEquipmentTypeEnum $type;

    #[ORM\Column]
    #[Groups(['laundry:read'])]
    private int $capacity;

    #[ORM\Column(type: 'float')]
    #[Groups(['laundry:read'])]
    private float $price;

    #[ORM\Column]
    #[Groups(['laundry:read'])]
    private int $duration;

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

    public function getEquipmentReference(): ?int
    {
        return $this->equipmentReference;
    }

    public function setEquipmentReference(?int $equipmentReference): static
    {
        $this->equipmentReference = $equipmentReference;
        return $this;
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

    public function getType(): LaundryEquipmentTypeEnum
    {
        return $this->type;
    }

    public function setType(LaundryEquipmentTypeEnum $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        $this->capacity = $capacity;
        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }
}
