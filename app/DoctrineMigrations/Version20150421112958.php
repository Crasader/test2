<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150421112958 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO background_process VALUES ('sync-cash-fake-entry', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '處理快開額度明細, 1/sec', 0, 0)");
        $this->addSql("INSERT INTO background_process VALUES ('sync-cash-fake-balance', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '更新快開額度餘額, 1/sec', 0, 0)");
        $this->addSql("INSERT INTO background_process VALUES ('sync-cash-fake-history', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '同步快開額度交易明細, 1/sec', 0, 0)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM background_process WHERE name = 'sync-cash-fake-entry'");
        $this->addSql("DELETE FROM background_process WHERE name = 'sync-cash-fake-balance'");
        $this->addSql("DELETE FROM background_process WHERE name = 'sync-cash-fake-history'");
    }
}
