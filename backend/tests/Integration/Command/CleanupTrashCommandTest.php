<?php

namespace App\Tests\Integration\Command;

use App\Entity\Note;
use App\Entity\User;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CleanupTrashCommandTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserFactory $userFactory;
    private User $user;
    private Note $noteTrashedOld;
    private Note $noteTrashedRecent;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
        self::bootKernel();

        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userFactory = new UserFactory(
            $this->entityManager,
            $container->get(UserPasswordHasherInterface::class),
        );

        $this->resetDatabase();
        $this->createTrashFixtures();
    }

    public function testCleanupTrashDeletesOldNotesAndKeepsRecentOnes(): void
    {
        $application = new Application(self::$kernel);
        $command = $application->find('app:cleanup-trash');
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('30 day(s)', $tester->getDisplay());

        $this->entityManager->clear();

        self::assertNull($this->entityManager->find(Note::class, $this->noteTrashedOld->getId()));
        $recent = $this->entityManager->find(Note::class, $this->noteTrashedRecent->getId());
        self::assertInstanceOf(Note::class, $recent);
        self::assertNotNull($recent->getDeletedAt());
    }

    private function resetDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DROP SCHEMA IF EXISTS public CASCADE');
        $connection->executeStatement('CREATE SCHEMA public');

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema($metadata);
    }

    private function createTrashFixtures(): void
    {
        $this->user = $this->userFactory->createUser('cleanup-trash@example.com');
        $this->noteTrashedOld = $this->userFactory->createNote($this->user, 'Old trashed', 'old content');
        $this->noteTrashedRecent = $this->userFactory->createNote($this->user, 'Recent trashed', 'recent content');

        $this->noteTrashedOld->setDeletedAt(new \DateTimeImmutable('-31 days'));
        $this->noteTrashedRecent->setDeletedAt(new \DateTimeImmutable('-1 day'));
        $this->entityManager->flush();
    }
}
