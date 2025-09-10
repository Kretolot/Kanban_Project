<?php

namespace App\Service;

use App\Entity\Board;
use App\Entity\User;
use Symfony\Contracts\Cache\CacheInterface;
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
        private CacheInterface $cache
    ) {}
    
    public function getBoardData(int $boardId, callable $callback): mixed
    {
        $cacheKey = sprintf('board_data_%d', $boardId);
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($callback) {
            $item->expiresAfter(self::BOARD_CACHE_TTL);
            return $callback();
        });
    }
    
    public function getUserBoards(int $userId, callable $callback): mixed
    {
        $cacheKey = sprintf('user_boards_%d', $userId);
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($callback) {
            $item->expiresAfter(self::USER_BOARDS_CACHE_TTL);
            return $callback();
        });
    }
    
    public function getUserStats(int $userId, callable $callback): mixed
    {
        $cacheKey = sprintf('user_stats_%d', $userId);
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($callback) {
            $item->expiresAfter(self::STATS_CACHE_TTL);
            return $callback();
        });
    }
    
    public function invalidateBoardCache(Board $board): void
    {
        // Invalidate specific board cache
        $this->cache->delete(sprintf('board_data_%d', $board->getId()));
        
        // Invalidate user boards cache
        $this->cache->delete(sprintf('user_boards_%d', $board->getOwner()->getId()));
        
        // Invalidate user stats cache
        $this->cache->delete(sprintf('user_stats_%d', $board->getOwner()->getId()));
    }
    
    public function invalidateUserCache(User $user): void
    {
        // Invalidate user boards cache
        $this->cache->delete(sprintf('user_boards_%d', $user->getId()));
        
        // Invalidate user stats cache  
        $this->cache->delete(sprintf('user_stats_%d', $user->getId()));
        
        // Invalidate all boards for this user
        foreach ($user->getBoards() as $board) {
            $this->cache->delete(sprintf('board_data_%d', $board->getId()));
        }
    }
    
    public function warmUpBoardCache(Board $board, callable $dataLoader): void
    {
        $cacheKey = sprintf('board_data_%d', $board->getId());
        
        $this->cache->get($cacheKey, function (ItemInterface $item) use ($dataLoader) {
            $item->expiresAfter(self::BOARD_CACHE_TTL);
            return $dataLoader();
        });
    }
    
    public function clearAllCache(): void
    {
        $this->cache->clear();
    }
    
    public function clearUserCache(int $userId): void
    {
        $this->cache->delete(sprintf('user_boards_%d', $userId));
        $this->cache->delete(sprintf('user_stats_%d', $userId));
    }
}