<?php

namespace App\Repository;

use App\Entity\LaundryNoteReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LaundryNoteReport>
 */
class LaundryNoteReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LaundryNoteReport::class);
    }

    /**
     * Renvoie les ids de LaundryNote signales, paginés, triés par nombre de signalements puis par signalement le plus récent.
     *
     * @return array<int, array{laundryNoteId: int, reportCount: int, lastReportedAt: \DateTimeInterface}>
     */
    public function findReportedCommentIdsPaginated(int $limit, int $offset): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.laundryNote) AS laundryNoteId')
            ->addSelect('COUNT(r.user) AS reportCount')
            ->addSelect('MAX(r.createdAt) AS lastReportedAt')
            ->innerJoin('r.laundryNote', 'ln')
            ->andWhere('ln.commentDeletedAt IS NULL')
            ->groupBy('r.laundryNote')
            ->orderBy('reportCount', 'DESC')
            ->addOrderBy('lastReportedAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return array_map(static fn(array $row) => [
            'laundryNoteId' => (int) $row['laundryNoteId'],
            'reportCount' => (int) $row['reportCount'],
            'lastReportedAt' => new \DateTime($row['lastReportedAt']),
        ], $rows);
    }

    public function countDistinctReportedComments(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(DISTINCT r.laundryNote)')
            ->innerJoin('r.laundryNote', 'ln')
            ->andWhere('ln.commentDeletedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return LaundryNoteReport[]
     */
    public function findByLaundryNote(\App\Entity\LaundryNote $laundryNote): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.laundryNote = :laundryNote')
            ->setParameter('laundryNote', $laundryNote)
            ->leftJoin('r.user', 'u')
            ->addSelect('u')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
