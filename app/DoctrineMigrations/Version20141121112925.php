<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141121112925 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX idx_cash_entry_created_at ON cash_entry");
        $this->addSql("DROP INDEX idx_cash_entry_cash_id_at ON cash_entry");
        $this->addSql("DROP INDEX idx_cash_transfer_entry_created_at ON cash_transfer_entry");
        $this->addSql("DROP INDEX idx_cash_transfer_entry_cash_id_at ON cash_transfer_entry");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE INDEX idx_cash_entry_created_at ON cash_entry (created_at)");
        $this->addSql("CREATE INDEX idx_cash_entry_cash_id_at ON cash_entry (cash_id, at)");
        $this->addSql("CREATE INDEX idx_cash_transfer_entry_created_at ON cash_transfer_entry (created_at)");
        $this->addSql("CREATE INDEX idx_cash_transfer_entry_cash_id_at ON cash_transfer_entry (cash_id, at)");
    }
}