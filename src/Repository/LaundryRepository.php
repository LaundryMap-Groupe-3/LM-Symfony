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
     * Find pending laundries with pagination
     */
    public function findPendingLaundries(int $limit = 10, int $offset = 0): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.status = :status')
            ->setParameter('status', LaundryStatusEnum::PENDING)
            ->orderBy('l.establishmentName', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count pending professionals
     */
    public function countPendingLaundries(): int
    {
        return $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.status = :status')
            ->setParameter('status', LaundryStatusEnum::PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
