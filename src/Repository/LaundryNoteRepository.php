<?php

namespace App\Repository;

use App\Entity\Laundry;
use App\Entity\LaundryNote;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LaundryNote>
 */
class LaundryNoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LaundryNote::class);
    }

     /**
     * Retourne la note moyenne et le nombre d'avis pour un tableau d'IDs de laveries
     * @param int[] $laundryIds
     * @return array|null
     */
    public function getAverageRatingAndCountByLaundryIds(array $laundryIds): ?array
    {
        if (empty($laundryIds)) {
            return null;
        }
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT AVG(rating) as avg_rating, COUNT(id) as review_count FROM laundry_note WHERE laundry_id IN (' . implode(',', array_fill(0, count($laundryIds), '?')) . ')';
        $stmt = $conn->executeQuery($sql, $laundryIds);
        return $stmt->fetchAssociative();
    }

    public function getCommentsByUser(User $user, int $offset, int $limit): ?array
    {
        return $this->createQueryBuilder('ln')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->where('ln.user = :user')
            ->setParameter('user', $user->getId())
            ->getQuery()
            ->getResult();
    }

    public function getCommentsByLaundry(Laundry $laundry, int $offset, int $limit): ?array
    {
        return $this->createQueryBuilder('ln')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->where('ln.laundry = :laundry')
            ->setParameter('laundry', $laundry->getId())
            ->getQuery()
            ->getResult();
    }

    public function countCommentsByUser(User $user): ?int
    {
        return $this->createQueryBuilder('ln')
            ->where('ln.user = :user')
            ->setParameter('user', $user->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countCommentsByLaundry(Laundry $laundry): ?int
    {
        return $this->createQueryBuilder('ln')
            ->select('COUNT(ln.id)')
            ->where('ln.laundry = :laundry')
            ->setParameter('laundry', $laundry->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getAverageRatingByLaundry(Laundry $laundry): ?int
    {
        return $this->createQueryBuilder('ln')
            ->select('AVG(ln.rating)')
            ->where('ln.laundry = :laundry')
            ->setParameter('laundry', $laundry->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }
}
