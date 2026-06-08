<?php

namespace App\Repository;

use App\Entity\OffensiveWord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OffensiveWord>
 */
class OffensiveWordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OffensiveWord::class);
    }

    public function findAllPaginated(int $limit, int $offset, string $search = ''): array
    {
        $qb = $this->createQueryBuilder('w')
            ->orderBy('w.label', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($search !== '') {
            $qb->andWhere('w.label LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function countAllFiltered(string $search = ''): int
    {
        $qb = $this->createQueryBuilder('w')
            ->select('COUNT(w.id)');

        if ($search !== '') {
            $qb->andWhere('w.label LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findOneByLabelInsensitive(string $label): ?OffensiveWord
    {
        return $this->createQueryBuilder('w')
            ->andWhere('LOWER(w.label) = LOWER(:label)')
            ->setParameter('label', $label)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return string[]
     */
    public function findAllLabels(): array
    {
        $rows = $this->createQueryBuilder('w')
            ->select('w.label')
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'label');
    }
}
