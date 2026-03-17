<?php

namespace App\Repository;

use App\Entity\EmailVerificationToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailVerificationToken>
 */
class EmailVerificationTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailVerificationToken::class);
    }

    public function findByToken(string $token): ?EmailVerificationToken
    {
        return $this->findOneBy(['token' => $token]);
    }

    public function findValidToken(string $token): ?EmailVerificationToken
    {
        $tokenEntity = $this->findByToken($token);

        if ($tokenEntity && $tokenEntity->isValid()) {
            return $tokenEntity;
        }

        return null;
    }

    public function findUnusedTokenForUser(User $user): ?EmailVerificationToken
    {
        return $this->findOneBy(['user' => $user, 'isUsed' => false]);
    }
}
