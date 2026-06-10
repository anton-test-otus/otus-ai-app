<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260609085911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE folders (id UUID NOT NULL, name VARCHAR(255) NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, user_id UUID NOT NULL, parent_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_FE37D30FA76ED395 ON folders (user_id)');
        $this->addSql('CREATE INDEX IDX_FE37D30F727ACA70 ON folders (parent_id)');
        $this->addSql('CREATE TABLE note_links (id UUID NOT NULL, source_note_id UUID NOT NULL, target_note_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_B49202CF6F32B03D ON note_links (source_note_id)');
        $this->addSql('CREATE INDEX IDX_B49202CFED85B13E ON note_links (target_note_id)');
        $this->addSql('CREATE UNIQUE INDEX source_target_unique ON note_links (source_note_id, target_note_id)');
        $this->addSql('CREATE TABLE note_versions (id UUID NOT NULL, content TEXT NOT NULL, title VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, note_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_9879C4C26ED0855 ON note_versions (note_id)');
        $this->addSql('CREATE TABLE notes (id UUID NOT NULL, title VARCHAR(255) NOT NULL, content TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, user_id UUID NOT NULL, folder_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_11BA68CA76ED395 ON notes (user_id)');
        $this->addSql('CREATE INDEX IDX_11BA68C162CB942 ON notes (folder_id)');
        $this->addSql('CREATE TABLE note_tags (note_id UUID NOT NULL, tag_id UUID NOT NULL, PRIMARY KEY (note_id, tag_id))');
        $this->addSql('CREATE INDEX IDX_CFC6857E26ED0855 ON note_tags (note_id)');
        $this->addSql('CREATE INDEX IDX_CFC6857EBAD26311 ON note_tags (tag_id)');
        $this->addSql('CREATE TABLE tags (id UUID NOT NULL, name VARCHAR(50) NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_6FBC9426A76ED395 ON tags (user_id)');
        $this->addSql('CREATE UNIQUE INDEX user_tag_unique ON tags (user_id, name)');
        $this->addSql('CREATE TABLE users (id UUID NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, autosave_delay_seconds INT DEFAULT NULL, version_consolidation_window_minutes INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('ALTER TABLE folders ADD CONSTRAINT FK_FE37D30FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE folders ADD CONSTRAINT FK_FE37D30F727ACA70 FOREIGN KEY (parent_id) REFERENCES folders (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE note_links ADD CONSTRAINT FK_B49202CF6F32B03D FOREIGN KEY (source_note_id) REFERENCES notes (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE note_links ADD CONSTRAINT FK_B49202CFED85B13E FOREIGN KEY (target_note_id) REFERENCES notes (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE note_versions ADD CONSTRAINT FK_9879C4C26ED0855 FOREIGN KEY (note_id) REFERENCES notes (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE notes ADD CONSTRAINT FK_11BA68CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE notes ADD CONSTRAINT FK_11BA68C162CB942 FOREIGN KEY (folder_id) REFERENCES folders (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE note_tags ADD CONSTRAINT FK_CFC6857E26ED0855 FOREIGN KEY (note_id) REFERENCES notes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE note_tags ADD CONSTRAINT FK_CFC6857EBAD26311 FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tags ADD CONSTRAINT FK_6FBC9426A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE folders DROP CONSTRAINT FK_FE37D30FA76ED395');
        $this->addSql('ALTER TABLE folders DROP CONSTRAINT FK_FE37D30F727ACA70');
        $this->addSql('ALTER TABLE note_links DROP CONSTRAINT FK_B49202CF6F32B03D');
        $this->addSql('ALTER TABLE note_links DROP CONSTRAINT FK_B49202CFED85B13E');
        $this->addSql('ALTER TABLE note_versions DROP CONSTRAINT FK_9879C4C26ED0855');
        $this->addSql('ALTER TABLE notes DROP CONSTRAINT FK_11BA68CA76ED395');
        $this->addSql('ALTER TABLE notes DROP CONSTRAINT FK_11BA68C162CB942');
        $this->addSql('ALTER TABLE note_tags DROP CONSTRAINT FK_CFC6857E26ED0855');
        $this->addSql('ALTER TABLE note_tags DROP CONSTRAINT FK_CFC6857EBAD26311');
        $this->addSql('ALTER TABLE tags DROP CONSTRAINT FK_6FBC9426A76ED395');
        $this->addSql('DROP TABLE folders');
        $this->addSql('DROP TABLE note_links');
        $this->addSql('DROP TABLE note_versions');
        $this->addSql('DROP TABLE notes');
        $this->addSql('DROP TABLE note_tags');
        $this->addSql('DROP TABLE tags');
        $this->addSql('DROP TABLE users');
    }
}
