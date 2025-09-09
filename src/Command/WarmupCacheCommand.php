<?php

namespace App\Command;

use App\Entity\User;
use App\Service\KanbanService;
use App\Service\CacheService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'kanban:cache:warmup',
    description: 'Warm up application cache for better performance'
)]
class WarmupCacheCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private KanbanService $kanbanService,
        private CacheService $cacheService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Warming up Kanban application cache');
        
        // Get all users
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $io->progressStart(count($users));
        
        foreach ($users as $user) {
            try {
                // Warm up user boards cache
                $this->kanbanService->getUserBoards($user);
                
                // Warm up user statistics cache
                $this->kanbanService->getUserStatistics($user);
                
                $io->progressAdvance();
            } catch (\Exception $e) {
                $io->error(sprintf('Failed to warm up cache for user %d: %s', $user->getId(), $e->getMessage()));
            }
        }
        
        $io->progressFinish();
        $io->success('Cache warmup completed successfully!');
        
        return Command::SUCCESS;
    }
}