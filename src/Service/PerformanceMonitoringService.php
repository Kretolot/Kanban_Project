<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Service for monitoring application performance
 */
class PerformanceMonitoringService
{
    private array $metrics = [];

    public function __construct(
        private LoggerInterface $logger,
        private Stopwatch $stopwatch
    ) {}

    public function startTimer(string $name): void
    {
        $this->stopwatch->start($name);
    }

    public function stopTimer(string $name): float
    {
        $event = $this->stopwatch->stop($name);
        $duration = $event->getDuration();
        
        $this->metrics[$name] = [
            'duration' => $duration,
            'memory' => $event->getMemory(),
            'timestamp' => time()
        ];
        
        // Log slow operations
        if ($duration > 1000) { // More than 1 second
            $this->logger->warning('Slow operation detected', [
                'operation' => $name,
                'duration' => $duration,
                'memory' => $event->getMemory()
            ]);
        }
        
        return $duration;
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function logDatabaseQuery(string $sql, array $params, float $duration): void
    {
        if ($duration > 100) { // More than 100ms
            $this->logger->info('Slow database query', [
                'sql' => $sql,
                'params' => $params,
                'duration' => $duration
            ]);
        }
    }

    public function logCacheHit(string $key): void
    {
        $this->logger->debug('Cache hit', ['key' => $key]);
    }

    public function logCacheMiss(string $key): void
    {
        $this->logger->info('Cache miss', ['key' => $key]);
    }
}