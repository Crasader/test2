<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140410093120 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE background_process SET name = 'sync-credit-entry', memo = '新增信用額度明細, 1/sec' WHERE name = 'run-credit-poper';");
        $this->addSql("UPDATE background_process SET name = 'sync-credit', memo = '同步信用額度, 1/sec' WHERE name = 'run-credit-sync';");
        $this->addSql("INSERT INTO background_process VALUES('sync-credit-period', 0, '2014-01-01 00:00:00', '2014-01-01 00:00:00', '同步累積金額資料, 1/sec', 0, 0);");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE background_process SET name = 'run-credit-poper', memo = '新增信用額度明細, 以及區間資料, 1/sec' WHERE name = 'sync-credit-entry';");
        $this->addSql("UPDATE background_process SET name = 'run-credit-sync', memo = '同步信用額度區間資料, 同步額度上限, 1/sec' WHERE name = 'sync-credit';");
        $this->addSql("DELETE FROM background_process WHERE name = 'sync-credit-period';");
    }
}
