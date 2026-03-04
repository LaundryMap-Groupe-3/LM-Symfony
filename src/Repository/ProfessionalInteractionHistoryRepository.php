<?php

namespace App\Repository;

use App\Entity\ProfessionalInteractionHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProfessionalInteractionHistory>
 */
class ProfessionalInteractionHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProfessionalInteractionHistory::class);
    }
}
