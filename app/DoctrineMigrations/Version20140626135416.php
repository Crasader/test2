<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140626135416 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE background_process SET name = 'sync-point-balance' WHERE name = 'sync_point_balance'");
        $this->addSql("UPDATE background_process SET name = 'sync-point-entry' WHERE name = 'sync_point_entry'");
        $this->addSql("UPDATE background_process SET name = 'sync-point-transaction' WHERE name = 'sync_point_transaction'");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE background_process SET name = 'sync_point_balance' WHERE name = 'sync-point-balance'");
        $this->addSql("UPDATE background_process SET name = 'sync_point_entry' WHERE name = 'sync-point-entry'");
        $this->addSql("UPDATE background_process SET name = 'sync_point_transaction' WHERE name = 'sync-point-transaction'");
    }
}
