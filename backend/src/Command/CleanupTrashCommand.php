<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-trash',
    description: 'Permanently delete notes from trash older than configured retention period'
)]
class CleanupTrashCommand extends Command
{
    public function __construct(
        private Connection $connection,
        private int $trashRetentionDays,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cutoffDate = (new \DateTimeImmutable())->modify(sprintf('-%d days', $this->trashRetentionDays));

        $sql = <<<SQL
DELETE FROM notes 
WHERE deleted_at IS NOT NULL 
AND deleted_at < :cutoffDate
SQL;

        $deletedCount = $this->connection->executeStatement($sql, [
            'cutoffDate' => $cutoffDate->format('Y-m-d H:i:s'),
        ]);

        $io->success(sprintf(
            'Cleanup completed. Permanently deleted %d note(s) older than %d day(s) (before %s).',
            $deletedCount,
            $this->trashRetentionDays,
            $cutoffDate->format('Y-m-d')
        ));

        return Command::SUCCESS;
    }
}
