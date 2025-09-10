<?php

namespace App\Service;

use App\Entity\Board;
use App\Entity\Col;
use App\Entity\Task;
use App\Entity\User;
use App\Repository\BoardRepository;
use App\Repository\ColRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Event\BoardUpdatedEvent;
use App\Event\ColumnUpdatedEvent;
use App\Event\TaskUpdatedEvent;

/**
 * Main business service for Kanban operations
 */
class KanbanService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BoardRepository $boardRepository,
        private ColRepository $colRepository,
        private TaskRepository $taskRepository,
        private CacheService $cacheService,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    // ========== BOARD OPERATIONS ==========
    
    public function createBoard(User $user, string $name): Board
    {
        
        $this->entityManager->beginTransaction();
        
        try {
            $board = new Board();
            $board->setName($name);
            $board->setOwner($user);
            
            $this->entityManager->persist($board);
            $this->entityManager->flush(); // Get board ID
            
            
            $this->entityManager->flush();
            $this->entityManager->commit();
            
            // Clear user cache
            $this->cacheService->invalidateUserCache($user);
            
            // Dispatch event
            $this->eventDispatcher->dispatch(new BoardUpdatedEvent($board), BoardUpdatedEvent::NAME);
            
            return $board;
            
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }
    
    public function getBoardWithData(int $boardId, User $user): ?Board
    {
        return $this->cacheService->getBoardData($boardId, function() use ($boardId, $user) {
            $board = $this->boardRepository->findBoardWithColumnsAndTasks($boardId);
            
            if (!$board || $board->getOwner() !== $user) {
                return null;
            }
            
            return $board;
        });
    }
    
    public function getUserBoards(User $user): array
    {
        return $this->cacheService->getUserBoards($user->getId(), function() use ($user) {
            return $this->boardRepository->findBy(['owner' => $user], ['createdAt' => 'DESC']);
        });
    }
    
    public function deleteBoard(Board $board): void
    {
        $this->entityManager->beginTransaction();
        
        try {
            $this->entityManager->remove($board);
            $this->entityManager->flush();
            $this->entityManager->commit();
            
            // Clear caches
            $this->cacheService->invalidateBoardCache($board);
            
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    // ========== COLUMN OPERATIONS ==========
    
    public function addColumn(Board $board, string $name): Col
    {
        $maxPosition = $this->colRepository->getMaxPositionForBoard($board);
        
        $column = new Col();
        $column->setName($name);
        $column->setPosition(($maxPosition ?? -1) + 1);
        $column->setBoard($board);
        
        $this->entityManager->persist($column);
        $this->entityManager->flush();
        
        // Clear cache
        $this->cacheService->invalidateBoardCache($board);
        
        // Dispatch event
        $this->eventDispatcher->dispatch(new ColumnUpdatedEvent($column), ColumnUpdatedEvent::NAME);
        
        return $column;
    }
    
    public function moveColumn(Col $column, int $newPosition): void
    {
        $this->entityManager->beginTransaction();
        
        try {
            $oldPosition = $column->getPosition();
            $board = $column->getBoard();

            // Pobierz wszystkie kolumny dla tej tablicy
            $columns = $this->colRepository->findBy(['board' => $board], ['position' => 'ASC']);

            // Reorganizuj pozycje innych kolumn
            if ($newPosition > $oldPosition) {
                // Przenosząc w prawo - zmniejsz pozycje kolumn między starą a nową pozycją
                foreach ($columns as $col) {
                    $pos = $col->getPosition();
                    if ($pos > $oldPosition && $pos <= $newPosition && $col->getId() !== $column->getId()) {
                        $col->setPosition($pos - 1);
                    }
                }
            } else {
                // Przenosząc w lewo - zwiększ pozycje kolumn między nową a starą pozycją
                foreach ($columns as $col) {
                    $pos = $col->getPosition();
                    if ($pos >= $newPosition && $pos < $oldPosition && $col->getId() !== $column->getId()) {
                        $col->setPosition($pos + 1);
                    }
                }
            }

            // Ustaw nową pozycję dla przenoszonej kolumny
            $column->setPosition($newPosition);
            
            // WAŻNE: Flush wszystkie zmiany do bazy danych
            $this->entityManager->flush();
            $this->entityManager->commit();
            
            // Clear cache
            $this->cacheService->invalidateBoardCache($board);
            
            // Dispatch event
            $this->eventDispatcher->dispatch(new ColumnUpdatedEvent($column), ColumnUpdatedEvent::NAME);
            
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }
    
    public function updateColumnName(Col $column, string $newName): void
    {
        $column->setName($newName);
        $this->entityManager->flush();
        
        // Clear cache
        $this->cacheService->invalidateBoardCache($column->getBoard());
        
        // Dispatch event
        $this->eventDispatcher->dispatch(new ColumnUpdatedEvent($column), ColumnUpdatedEvent::NAME);
    }
    
    public function deleteColumn(Col $column): void
    {
        $board = $column->getBoard();
        
        // Check if it's not the last column
        $columnsCount = $this->colRepository->count(['board' => $board]);
        if ($columnsCount <= 1) {
            throw new \LogicException('Nie można usunąć ostatniej kolumny z tablicy.');
        }
        
        // USUNIĘTO sprawdzanie czy kolumna ma zadania - teraz można usuwać kolumny z zadaniami
        // Zadania będą automatycznie usunięte przez orphanRemoval: true w relacji
        
        $this->entityManager->beginTransaction();
        
        try {
            $oldPosition = $column->getPosition();
            $this->entityManager->remove($column);
            
            // Update positions of remaining columns
            $remainingColumns = $this->colRepository->createQueryBuilder('c')
                ->where('c.board = :board')
                ->andWhere('c.position > :position')
                ->setParameter('board', $board)
                ->setParameter('position', $oldPosition)
                ->getQuery()
                ->getResult();

            foreach ($remainingColumns as $col) {
                $col->setPosition($col->getPosition() - 1);
            }
            
            $this->entityManager->flush();
            $this->entityManager->commit();
            
            // Clear cache
            $this->cacheService->invalidateBoardCache($board);
            
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    // ========== TASK OPERATIONS ==========
    
    public function createTask(Col $column, string $title, string $description = null): Task
    {
        $task = new Task();
        $task->setTitle($title);
        $task->setDescription($description);
        $task->setCol($column);
        $task->setPosition($column->getTasks()->count());
        
        $this->entityManager->persist($task);
        $this->entityManager->flush();
        
        // Clear cache
        $this->cacheService->invalidateBoardCache($column->getBoard());
        
        // Dispatch event
        $this->eventDispatcher->dispatch(new TaskUpdatedEvent($task), TaskUpdatedEvent::NAME);
        
        return $task;
    }
    
    public function moveTask(Task $task, Col $newColumn, int $newPosition = null): void
    {
        $oldBoard = $task->getCol()->getBoard();
        
        $task->setCol($newColumn);
        
        if ($newPosition !== null) {
            $task->setPosition($newPosition);
        }
        
        $this->entityManager->flush();
        
        // Clear cache for affected boards
        $this->cacheService->invalidateBoardCache($oldBoard);
        if ($newColumn->getBoard() !== $oldBoard) {
            $this->cacheService->invalidateBoardCache($newColumn->getBoard());
        }
        
        // Dispatch event
        $this->eventDispatcher->dispatch(new TaskUpdatedEvent($task), TaskUpdatedEvent::NAME);
    }
    
    public function updateTask(Task $task, string $title, string $description = null): void
    {
        $task->setTitle($title);
        $task->setDescription($description);
        
        $this->entityManager->flush();
        
        // Clear cache
        $this->cacheService->invalidateBoardCache($task->getCol()->getBoard());
        
        // Dispatch event
        $this->eventDispatcher->dispatch(new TaskUpdatedEvent($task), TaskUpdatedEvent::NAME);
    }
    
    public function deleteTask(Task $task): void
    {
        $board = $task->getCol()->getBoard();
        
        $this->entityManager->remove($task);
        $this->entityManager->flush();
        
        // Clear cache
        $this->cacheService->invalidateBoardCache($board);
    }

    // ========== STATISTICS AND SEARCH ==========
    
    public function getUserStatistics(User $user): array
    {
        return $this->cacheService->getUserStats($user->getId(), function() use ($user) {
            $totalTasks = 0;
            $boards = $this->boardRepository->findBy(['owner' => $user]);
            
            foreach ($boards as $board) {
                foreach ($board->getCols() as $col) {
                    $totalTasks += $col->getTasks()->count();
                }
            }
            
            return [
                'boardCount' => count($boards),
                'totalColumns' => array_sum(array_map(fn($board) => $board->getCols()->count(), $boards)),
                'totalTasks' => $totalTasks
            ];
        });
    }
    
    public function searchTasks(User $user, string $searchTerm): array
    {
        return $this->taskRepository->createQueryBuilder('t')
            ->join('t.col', 'c')
            ->join('c.board', 'b')
            ->where('b.owner = :user')
            ->andWhere('t.title LIKE :searchTerm OR t.description LIKE :searchTerm')
            ->setParameter('user', $user)
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }
    
    public function getBoardStatistics(Board $board): array
    {
        $statistics = [];
        foreach ($board->getCols() as $col) {
            $statistics[$col->getName()] = $col->getTasks()->count();
        }
        return $statistics;
    }
}