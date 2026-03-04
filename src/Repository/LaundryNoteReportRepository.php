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
}
