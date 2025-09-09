<?php

namespace App\EventListener;

use App\Event\BoardUpdatedEvent;
use App\Event\ColumnUpdatedEvent;
use App\Event\TaskUpdatedEvent;
use App\Service\CacheService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Psr\Log\LoggerInterface;

/**
 * Handles cache invalidation when entities are updated
 */
class CacheInvalidationListener
{
    public function __construct(
        private CacheService $cacheService,
        private LoggerInterface $logger
    ) {}

    #[AsEventListener(event: BoardUpdatedEvent::NAME)]
    public function onBoardUpdated(BoardUpdatedEvent $event): void
    {
        try {
            $this->cacheService->invalidateBoardCache($event->getBoard());
            $this->logger->info('Cache invalidated for board', ['board_id' => $event->getBoard()->getId()]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to invalidate board cache', [
                'board_id' => $event->getBoard()->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    #[AsEventListener(event: ColumnUpdatedEvent::NAME)]
    public function onColumnUpdated(ColumnUpdatedEvent $event): void
    {
        try {
            $this->cacheService->invalidateBoardCache($event->getColumn()->getBoard());
            $this->logger->info('Cache invalidated for column update', [
                'column_id' => $event->getColumn()->getId(),
                'board_id' => $event->getColumn()->getBoard()->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to invalidate cache for column update', [
                'column_id' => $event->getColumn()->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    #[AsEventListener(event: TaskUpdatedEvent::NAME)]
    public function onTaskUpdated(TaskUpdatedEvent $event): void
    {
        try {
            $this->cacheService->invalidateBoardCache($event->getTask()->getCol()->getBoard());
            $this->logger->info('Cache invalidated for task update', [
                'task_id' => $event->getTask()->getId(),
                'board_id' => $event->getTask()->getCol()->getBoard()->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to invalidate cache for task update', [
                'task_id' => $event->getTask()->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }
}