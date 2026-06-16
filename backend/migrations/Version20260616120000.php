<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add partial indexes on notes for user list and favorites queries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX notes_user_active_updated_idx ON notes (user_id, updated_at DESC) WHERE deleted_at IS NULL');
        $this->addSql('CREATE INDEX notes_user_favorite_active_updated_idx ON notes (user_id, updated_at DESC) WHERE deleted_at IS NULL AND is_favorite = true');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX notes_user_favorite_active_updated_idx');
        $this->addSql('DROP INDEX notes_user_active_updated_idx');
    }
}
