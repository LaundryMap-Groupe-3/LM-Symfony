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
     * Count pending laundries that are not soft-deleted.
     */
    public function countPendingLaundries(): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.status = :status')
            ->andWhere('l.deletedAt IS NULL')
            ->setParameter('status', LaundryStatusEnum::PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
