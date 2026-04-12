<?php

namespace App\Entity;

use App\Enum\GeolocalizationStatusEnum;
use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    #[Groups(['laundry:read'])]
    private string $address;

    #[ORM\Column(length: 255)]
    #[Groups(['laundry:read'])]
    private string $street;

    #[ORM\Column]
    #[Groups(['laundry:read'])]
    private int $postalCode;

    #[ORM\Column(length: 255)]
    #[Groups(['laundry:read'])]
    private string $city;

    #[ORM\Column(length: 255)]
    #[Groups(['laundry:read'])]
    private string $country;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Groups(['laundry:read'])]
    private ?float $latitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Groups(['laundry:read'])]
    private ?float $longitude = null;

    #[ORM\Column(type: 'string', enumType: GeolocalizationStatusEnum::class)]
    #[Groups(['laundry:read'])]
    private GeolocalizationStatusEnum $geolocalizationStatus;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;
        return $this;
    }

    public function getPostalCode(): int
    {
        return $this->postalCode;
    }

    public function setPostalCode(int $postalCode): static
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getGeolocalizationStatus(): GeolocalizationStatusEnum
    {
        return $this->geolocalizationStatus;
    }

    public function setGeolocalizationStatus(GeolocalizationStatusEnum $geolocalizationStatus): static
    {
        $this->geolocalizationStatus = $geolocalizationStatus;
        return $this;
    }
}
