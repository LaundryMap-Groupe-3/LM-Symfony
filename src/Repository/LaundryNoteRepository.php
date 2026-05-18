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

    /**
     * @param int[] $laundryIds
     * @return array<int, array{avg_rating: float|null, review_count: int}>
     */
    public function getAverageRatingAndCountByLaundryIdsGrouped(array $laundryIds): array
    {
        if (empty($laundryIds)) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();
        $placeholders = implode(',', array_fill(0, count($laundryIds), '?'));
        $sql = 'SELECT laundry_id, AVG(rating) as avg_rating, COUNT(id) as review_count FROM laundry_note WHERE laundry_id IN (' . $placeholders . ') GROUP BY laundry_id';
        $stmt = $conn->executeQuery($sql, $laundryIds);
        $rows = $stmt->fetchAllAssociative();

        $results = [];
        foreach ($rows as $row) {
            $laundryId = (int) $row['laundry_id'];
            $avgRating = $row['avg_rating'] !== null ? round((float) $row['avg_rating'], 2) : null;
            $results[$laundryId] = [
                'avg_rating' => $avgRating,
                'review_count' => (int) $row['review_count'],
            ];
        }

        return $results;
    }
}
