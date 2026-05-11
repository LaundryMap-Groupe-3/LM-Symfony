<?php

namespace App\Repository;

use App\Entity\UserBan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserBan>
 */
class UserBanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBan::class);
    }

    public function findActiveByUser($userId)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.user = :userId')
            ->andWhere('b.isPermanent = true OR (b.startedAt <= :now AND (b.endedAt IS NULL OR b.endedAt > :now))')
            ->setParameter('userId', $userId)
            ->setParameter('now', new \DateTime())
            ->orderBy('b.startedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUser($userId)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('b.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
