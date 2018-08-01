<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150508111221 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM background_process WHERE name = 'run-cashfake-poper'");
        $this->addSql("DELETE FROM background_process WHERE name = 'run-cashfake-sync'");
        $this->addSql("DELETE FROM background_process WHERE name = 'sync-cashfake-his-poper'");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO background_process VALUES ('run-cashfake-poper', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '處理假現金明細, 1/sec', 0, 0)");
        $this->addSql("INSERT INTO background_process VALUES ('run-cashfake-sync', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '更新假現金餘額, 1/sec', 0, 0)");
        $this->addSql("INSERT INTO background_process VALUES ('sync-cashfake-his-poper', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '同步快開額度交易明細資料, 1/sec', 0, 0)");
    }
}
