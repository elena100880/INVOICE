<?php

namespace App\Repository;

use App\Entity\InvoicePosition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method InvoicePosition|null find($id, $lockMode = null, $lockVersion = null)
 * @method InvoicePosition|null findOneBy(array $criteria, array $orderBy = null)
 * @method InvoicePosition[]    findAll()
 * @method InvoicePosition[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoicePositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvoicePosition::class);
    }

    // /**
    //  * @return InvoicePosition[] Returns an array of InvoicePosition objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?InvoicePosition
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
