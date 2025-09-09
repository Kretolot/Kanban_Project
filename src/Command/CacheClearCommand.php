<?php

namespace App\Command;

use App\Service\CacheService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'kanban:cache:clear',
    description: 'Clear application cache'
)]
class CacheClearCommand extends Command
{
    public function __construct(
        private CacheService $cacheService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'user',
            'u',
            InputOption::VALUE_OPTIONAL,
            'Clear cache for specific user ID'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $userId = $input->getOption('user');
        
        if ($userId) {
            $this->cacheService->clearUserCache((int)$userId);
            $io->success(sprintf('Cache cleared for user %d', $userId));
        } else {
            $this->cacheService->clearAllCache();
            $io->success('All application cache cleared');
        }
        
        return Command::SUCCESS;
    }
}