<?php

namespace App\Repository;

use App\Entity\Board;
use App\Entity\User; // Dodaj brakujący import
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Board>
 */
class BoardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Board::class);
    }

    /**
     * Find user boards with task counts - optimized single query
     */
    public function findUserBoardsWithCounts(User $user): array
    {
        return $this->createQueryBuilder('b')
            ->select('b', 'c', 'COUNT(t.id) as taskCount')
            ->leftJoin('b.cols', 'c')
            ->leftJoin('c.tasks', 't')
            ->where('b.owner = :user')
            ->setParameter('user', $user)
            ->groupBy('b.id', 'c.id')
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find board with all columns and tasks in a single optimized query
     */
    public function findBoardWithColumnsAndTasks(int $boardId): ?Board
    {
        return $this->createQueryBuilder('b')
            ->select('b', 'c', 't')
            ->leftJoin('b.cols', 'c')
            ->leftJoin('c.tasks', 't')
            ->where('b.id = :boardId')
            ->setParameter('boardId', $boardId)
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('t.position', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return Board[] Returns an array of Board objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Board
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}