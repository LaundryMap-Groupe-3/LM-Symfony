<?php

namespace App\Repository;

use App\Entity\LaundryInteractionHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LaundryInteractionHistory>
 */
class LaundryInteractionHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LaundryInteractionHistory::class);
    }
}
