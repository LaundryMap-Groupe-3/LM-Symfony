<?php

namespace App\Repository;

use App\Entity\Laundry;
use App\Enum\LaundryStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Laundry>
 */
class LaundryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Laundry::class);
    }

    /**
     * Find pending professionals with pagination
     */
    public function findPendingLaundries(int $limit = 10, int $offset = 0): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.address', 'addr')
            ->addSelect('addr')
            ->leftJoin('l.professional', 'pro')
            ->addSelect('pro')
            ->leftJoin('pro.user', 'u')
            ->addSelect('u')
            ->where('l.status = :status')
            ->andWhere('l.deletedAt IS NULL')
            ->setParameter('status', LaundryStatusEnum::PENDING)
            ->orderBy('l.establishmentName', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countPendingLaundries(): int
    {
        return $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.status = :status')
            ->andWhere('l.deletedAt IS NULL')
            ->setParameter('status', LaundryStatusEnum::PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
