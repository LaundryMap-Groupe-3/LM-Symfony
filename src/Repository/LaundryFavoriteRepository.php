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
        $laundryIds = $this->createQueryBuilder('fl')
            ->select('IDENTITY(fl.laundry) as laundry_id')
            ->where('fl.user = :user')
            ->setParameter('user', $user->getId())
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getSingleColumnResult();

        if (empty($laundryIds)) {
            return [];
        }

        return $this->createQueryBuilder('fl')
            ->addSelect('l', 'address', 'logo', 'closures', 'exceptionalClosures')
            ->leftJoin('fl.laundry', 'l')
            ->leftJoin('l.address', 'address')
            ->leftJoin('l.logo', 'logo')
            ->leftJoin('l.laundryClosures', 'closures')
            ->leftJoin('l.laundryExceptionalClosures', 'exceptionalClosures')
            ->where('fl.user = :user')
            ->andWhere('IDENTITY(fl.laundry) IN (:laundryIds)')
            ->setParameter('user', $user->getId())
            ->setParameter('laundryIds', $laundryIds)
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
