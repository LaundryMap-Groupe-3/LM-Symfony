<?php

namespace App\Repository;

use App\Entity\Professional;
use App\Enum\ProfessionalStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Professional>
 */
class ProfessionalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Professional::class);
    }

    /**
     * Find pending professionals with pagination
     */
    public function findPendingProfessionals(int $limit = 10, int $offset = 0): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', ProfessionalStatusEnum::PENDING)
            ->orderBy('p.user', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count pending professionals
     */
    public function countPendingProfessionals(): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.status = :status')
            ->setParameter('status', ProfessionalStatusEnum::PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
