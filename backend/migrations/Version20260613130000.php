<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional icon column to folders';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE folders ADD icon VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE folders DROP icon');
    }
}
