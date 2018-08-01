<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140814175617 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX idx_transcribe_entry_force_confirm_at ON transcribe_entry");
        $this->addSql("ALTER TABLE transcribe_entry ADD username VARCHAR(20) DEFAULT NULL AFTER remit_entry_id, CHANGE force_confirm_at confirm_at DATETIME DEFAULT NULL");
        $this->addSql("CREATE INDEX idx_transcribe_entry_confirm_at ON transcribe_entry (confirm_at)");
        $this->addSql("UPDATE transcribe_entry AS te, remit_entry AS re SET te.username = re.username, te.confirm_at = re.confirm_at WHERE te.remit_entry_id = re.id");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX idx_transcribe_entry_confirm_at ON transcribe_entry");
        $this->addSql("ALTER TABLE transcribe_entry DROP username, CHANGE confirm_at force_confirm_at DATETIME DEFAULT NULL");
        $this->addSql("CREATE INDEX idx_transcribe_entry_force_confirm_at ON transcribe_entry (force_confirm_at)");
    }
}
