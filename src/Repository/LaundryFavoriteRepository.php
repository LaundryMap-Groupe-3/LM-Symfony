<?php

namespace App\Repository;

use App\Entity\LaundryFavorite;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LaundryFavorite>
 */
class LaundryFavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LaundryFavorite::class);
    }

    public function getFavoritesLaundriesByUser(int $offset, int $limit, User $user): array
    {
        return $this->createQueryBuilder('fl')
            ->addSelect('l')
            ->leftJoin('fl.laundry', 'l')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->where('fl.user = :user')
            ->setParameter('user', $user->getId())
            ->getQuery()
            ->getResult();
    }

    public function countFavoritesLaundriesByUser(User $user): int
    {
        return $this->createQueryBuilder('fl')
            ->select('COUNT(l.id)')
            ->leftJoin('fl.laundry', 'l')
            ->where('fl.user = :user')
            ->setParameter('user', $user->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getFavoritesLaundriesIdsByUser(User $user): array
    {
        $results = $this->createQueryBuilder('fl')
            ->select('IDENTITY(fl.laundry) as id')
            ->where('fl.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getArrayResult();

        return array_column($results, 'id');
    }
}
