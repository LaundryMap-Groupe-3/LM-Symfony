<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin1 = new Admin();
        $admin1->setEmail('admin@example.com');
        $admin1->setPassword($this->passwordHasher->hashPassword($admin1, 'admin123'));
        $manager->persist($admin1);

        $admin2 = new Admin();
        $admin2->setEmail('superadmin@example.com');
        $admin2->setPassword($this->passwordHasher->hashPassword($admin2, 'super123'));
        $manager->persist($admin2);

        $manager->flush();
    }
}
