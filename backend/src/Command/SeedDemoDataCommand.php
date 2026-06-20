<?php

namespace App\Command;

use App\DemoSeed\DemoUniverseDefinition;
use App\DemoSeed\DemoUniverseSeeder;
use App\DemoSeed\Universe\PotterUniverse;
use App\DemoSeed\Universe\WesterosUniverse;
use App\DemoSeed\Universe\WitcherUniverse;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-demo-data',
    description: 'Наполнить БД demo-данными (3 вселенные × ~40 заметок)',
)]
class SeedDemoDataCommand extends Command
{
    /** @var list<string> */
    public const DEMO_EMAILS = [
        'hogwarts@demo.local',
        'westeros@demo.local',
        'witcher@demo.local',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private DemoUniverseSeeder $demoUniverseSeeder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Удалить существующих demo-пользователей и пересоздать данные',
        );
        $this->addOption(
            'if-missing',
            null,
            InputOption::VALUE_NONE,
            'Пропустить загрузку, если demo-пользователи уже существуют',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');
        $ifMissing = (bool) $input->getOption('if-missing');

        $existingUsers = $this->findExistingDemoUsers();
        if ($existingUsers !== [] && !$force) {
            if ($ifMissing) {
                $io->note('Demo-данные уже загружены, пропуск.');

                return Command::SUCCESS;
            }

            $io->warning('Demo-пользователи уже существуют: '.implode(', ', array_map(
                static fn (User $user): string => (string) $user->getEmail(),
                $existingUsers,
            )));
            $io->note('Используйте --force для удаления и пересоздания demo-данных.');

            return Command::FAILURE;
        }

        if ($existingUsers !== [] && $force) {
            foreach ($existingUsers as $user) {
                $this->entityManager->remove($user);
            }
            $this->entityManager->flush();
            $io->writeln('Существующие demo-пользователи удалены.');
        }

        $definitions = [
            PotterUniverse::definition(),
            WesterosUniverse::definition(),
            WitcherUniverse::definition(),
        ];

        $io->title('Загрузка demo-данных');
        $io->writeln(sprintf('Пароль для всех demo-пользователей: %s', DemoUniverseDefinition::DEMO_PASSWORD));
        $io->newLine();

        foreach ($definitions as $definition) {
            $result = $this->demoUniverseSeeder->seed($definition);

            $io->section($definition->email);
            $io->listing([
                sprintf('Папок: %d', $result->folderCount),
                sprintf('Тегов: %d', $result->tagCount),
                sprintf('Заметок: %d', $result->noteCount),
                sprintf('Избранных: %d', $result->favoriteCount),
                sprintf('Wiki-связей (note_links): %d', $result->linkCount),
                sprintf('Версий: %d', $result->versionCount),
            ]);
        }

        $io->success('Demo-данные загружены.');
        $io->note('Администратор создаётся отдельно: php bin/console app:create-admin');

        return Command::SUCCESS;
    }

    /**
     * @return list<User>
     */
    private function findExistingDemoUsers(): array
    {
        $users = [];

        foreach (self::DEMO_EMAILS as $email) {
            $user = $this->userRepository->findOneBy(['email' => $email]);
            if ($user !== null) {
                $users[] = $user;
            }
        }

        return $users;
    }
}
