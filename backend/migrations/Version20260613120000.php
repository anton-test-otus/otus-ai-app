<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add aliases JSONB column to note_links for wiki-link display labels';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE note_links ADD aliases JSONB NOT NULL DEFAULT '[]'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE note_links DROP aliases');
    }
}
