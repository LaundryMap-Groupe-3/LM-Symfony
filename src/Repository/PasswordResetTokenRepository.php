<?php

namespace App\Repository;

use App\Entity\PasswordResetToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetToken>
 */
class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    /**
     * Find valid token by token string
     */
    public function findValidToken(string $token): ?PasswordResetToken
    {
        return $this->createQueryBuilder('prt')
            ->where('prt.token = :token')
            ->andWhere('prt.isUsed = false')
            ->andWhere('prt.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find expired or used tokens for cleanup
     */
    public function findExpiredTokens(\DateTime $before): array
    {
        return $this->createQueryBuilder('prt')
            ->where('prt.expiresAt < :date')
            ->orWhere('(prt.isUsed = true AND prt.usedAt < :date)')
            ->setParameter('date', $before)
            ->getQuery()
            ->getResult();
    }
}
