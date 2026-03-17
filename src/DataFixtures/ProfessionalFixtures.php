<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Professional;
use App\Entity\Address;
use App\Enum\UserStatusEnum;
use App\Enum\ProfessionalStatusEnum;
use App\Enum\GeolocalizationStatusEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfessionalFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Addresses
        $address1 = new Address();
        $address1->setAddress('123 Rue de la Paix');
        $address1->setStreet('Rue de la Paix');
        $address1->setPostalCode(75001);
        $address1->setCity('Paris');
        $address1->setCountry('France');
        $address1->setLatitude(48.8566);
        $address1->setLongitude(2.3522);
        $address1->setGeolocalizationStatus(GeolocalizationStatusEnum::VERIFIED);
        $manager->persist($address1);

        $address2 = new Address();
        $address2->setAddress('45 Avenue des Champs-Élysées');
        $address2->setStreet('Avenue des Champs-Élysées');
        $address2->setPostalCode(75008);
        $address2->setCity('Paris');
        $address2->setCountry('France');
        $address2->setLatitude(48.8698);
        $address2->setLongitude(2.3075);
        $address2->setGeolocalizationStatus(GeolocalizationStatusEnum::VERIFIED);
        $manager->persist($address2);

        $address3 = new Address();
        $address3->setAddress('10 Place Bellecour');
        $address3->setStreet('Place Bellecour');
        $address3->setPostalCode(69002);
        $address3->setCity('Lyon');
        $address3->setCountry('France');
        $address3->setLatitude(45.7579);
        $address3->setLongitude(4.8320);
        $address3->setGeolocalizationStatus(GeolocalizationStatusEnum::VERIFIED);
        $manager->persist($address3);

        // Professional 1
        $userPro1 = new User();
        $userPro1->setEmail('pierre.laverie@example.com');
        $userPro1->setPassword($this->passwordHasher->hashPassword($userPro1, 'password123'));
        $userPro1->setFirstName('Pierre');
        $userPro1->setLastName('Martin');
        $userPro1->setStatus(UserStatusEnum::VERIFIED);
        $userPro1->setCreatedAt(new \DateTime('2025-11-10'));
        $userPro1->setLastLoginAt(new \DateTime('2026-03-05'));
        $manager->persist($userPro1);

        $professional1 = new Professional();
        $professional1->setUser($userPro1);
        $professional1->setSiret(12345678912345);
        $professional1->setStatus(ProfessionalStatusEnum::APPROVED);
        $professional1->setValidationDate(new \DateTime('2025-11-15'));
        $professional1->setAddress($address1);
        $manager->persist($professional1);

        // Professional 2
        $userPro2 = new User();
        $userPro2->setEmail('claire.pressing@example.com');
        $userPro2->setPassword($this->passwordHasher->hashPassword($userPro2, 'password123'));
        $userPro2->setFirstName('Claire');
        $userPro2->setLastName('Bernard');
        $userPro2->setStatus(UserStatusEnum::VERIFIED);
        $userPro2->setCreatedAt(new \DateTime('2026-01-20'));
        $userPro2->setLastLoginAt(new \DateTime('2026-03-04'));
        $manager->persist($userPro2);

        $professional2 = new Professional();
        $professional2->setUser($userPro2);
        $professional2->setSiret(98765432198765);
        $professional2->setStatus(ProfessionalStatusEnum::APPROVED);
        $professional2->setValidationDate(new \DateTime('2026-01-25'));
        $professional2->setAddress($address2);
        $manager->persist($professional2);

        // Professional 3
        $userPro3 = new User();
        $userPro3->setEmail('thomas.lavomatic@example.com');
        $userPro3->setPassword($this->passwordHasher->hashPassword($userPro3, 'password123'));
        $userPro3->setFirstName('Thomas');
        $userPro3->setLastName('Durand');
        $userPro3->setStatus(UserStatusEnum::PENDING);
        $userPro3->setCreatedAt(new \DateTime('2026-03-01'));
        $manager->persist($userPro3);

        $professional3 = new Professional();
        $professional3->setUser($userPro3);
        $professional3->setSiret(55566677799900);
        $professional3->setStatus(ProfessionalStatusEnum::PENDING);
        $professional3->setAddress($address3);
        $manager->persist($professional3);

        $manager->flush();
    }
}
