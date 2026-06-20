<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260620120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add PostgreSQL full-text search vector (russian) and GIN index on notes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE notes ADD COLUMN search_vector tsvector
              GENERATED ALWAYS AS (
                to_tsvector('russian', coalesce(title, '') || ' ' || coalesce(content, ''))
              ) STORED
            SQL);
        $this->addSql('CREATE INDEX notes_search_vector_gin_idx ON notes USING GIN (search_vector)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX notes_search_vector_gin_idx');
        $this->addSql('ALTER TABLE notes DROP COLUMN search_vector');
    }
}
