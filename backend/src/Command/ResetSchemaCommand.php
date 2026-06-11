<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:reset-schema',
    description: 'Удалить схему БД и повторно применить миграции',
)]
class ResetSchemaCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            'no-migrate',
            null,
            InputOption::VALUE_NONE,
            'Только удалить схему, без применения миграций'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $application = $this->getApplication();

        if ($application === null) {
            $io->error('Консольное приложение недоступно');
            return Command::FAILURE;
        }

        $io->warning('Будут удалены все таблицы и данные в текущей БД.');

        $dropCommand = $application->find('doctrine:schema:drop');
        $dropExitCode = $dropCommand->run(
            new ArrayInput(['--force' => true, '--full-database' => true]),
            $output
        );

        if ($dropExitCode !== Command::SUCCESS) {
            return $dropExitCode;
        }

        $io->success('Схема БД удалена');

        if ($input->getOption('no-migrate')) {
            return Command::SUCCESS;
        }

        $migrateCommand = $application->find('doctrine:migrations:migrate');
        $migrateExitCode = $migrateCommand->run(
            new ArrayInput(['--no-interaction' => true]),
            $output
        );

        if ($migrateExitCode !== Command::SUCCESS) {
            return $migrateExitCode;
        }

        $io->success('Миграции применены');

        return Command::SUCCESS;
    }
}
