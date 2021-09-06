<?php

namespace App\Repository;

use App\Entity\GeneratorUploads;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GeneratorUploads|null find($id, $lockMode = null, $lockVersion = null)
 * @method GeneratorUploads|null findOneBy(array $criteria, array $orderBy = null)
 * @method GeneratorUploads[]    findAll()
 * @method GeneratorUploads[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GeneratorUploadsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GeneratorUploads::class);
    }

    // /**
    //  * @return GeneratorUploads[] Returns an array of GeneratorUploads objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?GeneratorUploads
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
