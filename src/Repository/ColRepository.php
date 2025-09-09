<?php

namespace App\Repository;

use App\Entity\Board;
use App\Entity\Col;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Col>
 */
class ColRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Col::class);
    }

    /**
     * Get maximum position for board - optimized single query
     */
    public function getMaxPositionForBoard(Board $board): ?int
    {
        return $this->createQueryBuilder('c')
            ->select('MAX(c.position)')
            ->where('c.board = :board')
            ->setParameter('board', $board)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Update column positions efficiently with bulk operations
     */
    public function updateColumnPositions(Col $movedColumn, int $newPosition): void
    {
        $oldPosition = $movedColumn->getPosition();
        $board = $movedColumn->getBoard();

        if ($newPosition > $oldPosition) {
            // Moving right - decrement positions between old and new position
            $this->createQueryBuilder('c')
                ->update()
                ->set('c.position', 'c.position - 1')
                ->where('c.board = :board')
                ->andWhere('c.position > :oldPosition')
                ->andWhere('c.position <= :newPosition')
                ->andWhere('c.id != :movedColumnId')
                ->setParameter('board', $board)
                ->setParameter('oldPosition', $oldPosition)
                ->setParameter('newPosition', $newPosition)
                ->setParameter('movedColumnId', $movedColumn->getId())
                ->getQuery()
                ->execute();
        } else {
            // Moving left - increment positions between new and old position
            $this->createQueryBuilder('c')
                ->update()
                ->set('c.position', 'c.position + 1')
                ->where('c.board = :board')
                ->andWhere('c.position >= :newPosition')
                ->andWhere('c.position < :oldPosition')
                ->andWhere('c.id != :movedColumnId')
                ->setParameter('board', $board)
                ->setParameter('newPosition', $newPosition)
                ->setParameter('oldPosition', $oldPosition)
                ->setParameter('movedColumnId', $movedColumn->getId())
                ->getQuery()
                ->execute();
        }

        // Update the moved column position
        $movedColumn->setPosition($newPosition);
    }

    /**
     * Decrement positions after deleted column
     */
    public function decrementPositionsAfter(Board $board, int $deletedPosition): void
    {
        $this->createQueryBuilder('c')
            ->update()
            ->set('c.position', 'c.position - 1')
            ->where('c.board = :board')
            ->andWhere('c.position > :deletedPosition')
            ->setParameter('board', $board)
            ->setParameter('deletedPosition', $deletedPosition)
            ->getQuery()
            ->execute();
    }

    /**
     * Find columns with task counts
     */
    public function findColumnsWithTaskCounts(Board $board): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'COUNT(t.id) as taskCount')
            ->leftJoin('c.tasks', 't')
            ->where('c.board = :board')
            ->setParameter('board', $board)
            ->groupBy('c.id')
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get columns for board ordered by position
     */
    public function findByBoardOrdered(Board $board): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.board = :board')
            ->setParameter('board', $board)
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Col[] Returns an array of Col objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Col
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}