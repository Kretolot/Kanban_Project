<?php

namespace App\Service;

use App\Entity\Board;
use App\Entity\User;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service for managing application cache
 */
class CacheService
{
    private const BOARD_CACHE_TTL = 3600; // 1 hour
    private const USER_BOARDS_CACHE_TTL = 1800; // 30 minutes
    private const STATS_CACHE_TTL = 7200; // 2 hours
    
    public function __construct(
        private TagAwareAdapterInterface $cache
    ) {}
    
    public function getBoardData(int $boardId, callable $callback): mixed
    {
        $cacheKey = sprintf('board_data_%d', $boardId);
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($callback, $boardId) {
            $item->expiresAfter(self::BOARD_CACHE_TTL);
            $item->tag(['board', sprintf('board_%d', $boardId)]);
            
            return $callback();
        });
    }
    
    public function getUserBoards(int $userId, callable $callback): mixed
    {
        $cacheKey = sprintf('user_boards_%d', $userId);
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($callback, $userId) {
            $item->expiresAfter(self::USER_BOARDS_CACHE_TTL);
            $item->tag(['user_boards', sprintf('user_%d', $userId)]);
            
            return $callback();
        });
    }
    
    public function getUserStats(int $userId, callable $callback): mixed
    {
        $cacheKey = sprintf('user_stats_%d', $userId);
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($callback, $userId) {
            $item->expiresAfter(self::STATS_CACHE_TTL);
            $item->tag(['user_stats', sprintf('user_%d', $userId)]);
            
            return $callback();
        });
    }
    
    public function invalidateBoardCache(Board $board): void
    {
        $this->cache->invalidateTags([
            sprintf('board_%d', $board->getId()),
            sprintf('user_%d', $board->getOwner()->getId())
        ]);
    }
    
    public function invalidateUserCache(User $user): void
    {
        $this->cache->invalidateTags([
            sprintf('user_%d', $user->getId()),
            sprintf('user_boards_%d', $user->getId()),
            sprintf('user_stats_%d', $user->getId())
        ]);
    }
    
    public function warmUpBoardCache(Board $board, callable $dataLoader): void
    {
        $cacheKey = sprintf('board_data_%d', $board->getId());
        
        $this->cache->get($cacheKey, function (ItemInterface $item) use ($dataLoader, $board) {
            $item->expiresAfter(self::BOARD_CACHE_TTL);
            $item->tag(['board', sprintf('board_%d', $board->getId())]);
            
            return $dataLoader();
        });
    }
    
    public function clearAllCache(): void
    {
        $this->cache->clear();
    }
    
    public function clearUserCache(int $userId): void
    {
        $this->cache->invalidateTags([sprintf('user_%d', $userId)]);
    }
}