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
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->leftJoin('p.address', 'addr')
            ->addSelect('addr')
            ->where('p.status = :status')
            ->setParameter('status', ProfessionalStatusEnum::PENDING)
            ->orderBy('u.lastName', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findAllProfessionals(int $limit = 10, int $offset = 0, string $search = '', ?ProfessionalStatusEnum $status = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->leftJoin('p.address', 'addr')
            ->addSelect('addr');

        if ($search !== '') {
            $qb->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search OR p.companyName LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($status !== null) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->orderBy('u.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countAllProfessionalsFiltered(string $search = '', ?ProfessionalStatusEnum $status = null): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->leftJoin('p.user', 'u');

        if ($search !== '') {
            $qb->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search OR p.companyName LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($status !== null) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function countAllProfessionals(): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
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
