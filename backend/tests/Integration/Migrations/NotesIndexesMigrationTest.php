<?php

namespace App\Tests\Integration\Migrations;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NotesIndexesMigrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
        self::bootKernel();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->resetDatabase();
    }

    public function testNotesPartialIndexesExistAfterMigration(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement(
            'CREATE INDEX notes_user_active_updated_idx ON notes (user_id, updated_at DESC) WHERE deleted_at IS NULL',
        );
        $connection->executeStatement(
            'CREATE INDEX notes_user_favorite_active_updated_idx ON notes (user_id, updated_at DESC) WHERE deleted_at IS NULL AND is_favorite = true',
        );

        $indexes = $connection->fetchFirstColumn(
            <<<'SQL'
                SELECT indexname
                FROM pg_indexes
                WHERE schemaname = 'public'
                  AND tablename = 'notes'
                  AND indexname IN (
                      'notes_user_active_updated_idx',
                      'notes_user_favorite_active_updated_idx'
                  )
            SQL,
        );

        sort($indexes);
        self::assertSame(
            ['notes_user_active_updated_idx', 'notes_user_favorite_active_updated_idx'],
            $indexes,
        );
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
}
