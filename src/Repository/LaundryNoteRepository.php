<?php

namespace App\Repository;

use App\Entity\LaundryNote;
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
}
