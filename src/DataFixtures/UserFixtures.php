<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Enum\UserStatusEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const USER1_REFERENCE = 'user-1';
    public const USER2_REFERENCE = 'user-2';
    public const USER3_REFERENCE = 'user-3';
    public const USER4_REFERENCE = 'user-4';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $user1 = new User();
        $user1->setEmail('marie.dupont@example.com');
        $user1->setPassword($this->passwordHasher->hashPassword($user1, 'password123'));
        $user1->setFirstName('Marie');
        $user1->setLastName('Dupont');
        $user1->setStatus(UserStatusEnum::VERIFIED);
        $user1->setEmailVerifiedAt(new \DateTime('2026-01-16'));
        $user1->setCreatedAt(new \DateTime('2026-01-15'));
        $user1->setLastLoginAt(new \DateTime('2026-03-04'));
        $manager->persist($user1);
        $this->addReference(self::USER1_REFERENCE, $user1);

        $user2 = new User();
        $user2->setEmail('jean.martin@example.com');
        $user2->setPassword($this->passwordHasher->hashPassword($user2, 'password123'));
        $user2->setFirstName('Jean');
        $user2->setLastName('Martin');
        $user2->setStatus(UserStatusEnum::VERIFIED);
        $user2->setEmailVerifiedAt(new \DateTime('2026-02-02'));
        $user2->setCreatedAt(new \DateTime('2026-02-01'));
        $user2->setLastLoginAt(new \DateTime('2026-03-05'));
        $manager->persist($user2);
        $this->addReference(self::USER2_REFERENCE, $user2);

        $user3 = new User();
        $user3->setEmail('sophie.bernard@example.com');
        $user3->setPassword($this->passwordHasher->hashPassword($user3, 'password123'));
        $user3->setFirstName('Sophie');
        $user3->setLastName('Bernard');
        $user3->setStatus(UserStatusEnum::PENDING);
        $user3->setCreatedAt(new \DateTime('2026-03-03'));
        $manager->persist($user3);
        $this->addReference(self::USER3_REFERENCE, $user3);

        $user4 = new User();
        $user4->setEmail('luc.petit@example.com');
        $user4->setPassword($this->passwordHasher->hashPassword($user4, 'password123'));
        $user4->setFirstName('Luc');
        $user4->setLastName('Petit');
        $user4->setStatus(UserStatusEnum::SUSPENDED);
        $user4->setCreatedAt(new \DateTime('2025-12-01'));
        $user4->setLastLoginAt(new \DateTime('2026-02-20'));
        $manager->persist($user4);
        $this->addReference(self::USER4_REFERENCE, $user4);

        $manager->flush();
    }
}
