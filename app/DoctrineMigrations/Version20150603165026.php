<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150603165026 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `last_success`, `memo`, `num`, `msg_num`) VALUES ('check-chip-entry', 1, '2015-01-01 00:00:00', '2015-01-01 00:00:00', NULL, '檢查籌碼交易明細, 1/hour', 0, 0)");
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `last_success`, `memo`, `num`, `msg_num`) VALUES ('check-coin-entry', 0, '2015-01-01 00:00:00', '2015-01-01 00:00:00', NULL, '檢查點數交易明細, 1/hour', 0, 0)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM `background_process` WHERE `name` = 'check-chip-entry'");
        $this->addSql("DELETE FROM `background_process` WHERE `name` = 'check-coin-entry'");
    }
}
