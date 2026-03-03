<?php

namespace App\Repository;

use App\Entity\LaundryExceptionalClosure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LaundryExceptionalClosure>
 */
class LaundryExceptionalClosureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LaundryExceptionalClosure::class);
    }
}
