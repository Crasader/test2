<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150612155338 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `last_success`, `memo`, `num`, `msg_num`) VALUES ('check-chip-error', 1, '2015-01-01 00:00:00', '2015-01-01 00:00:00', NULL, '檢查籌碼明細, 1/hour', 0, 0)");
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `last_success`, `memo`, `num`, `msg_num`) VALUES ('check-margin-error', 1, '2015-01-01 00:00:00', '2015-01-01 00:00:00', NULL, '檢查保證金明細, 1/hour', 0, 0)");
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `last_success`, `memo`, `num`, `msg_num`) VALUES ('check-coin-error', 0, '2015-01-01 00:00:00', '2015-01-01 00:00:00', NULL, '檢查點數明細, 1/hour', 0, 0)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM `background_process` WHERE `name` = 'check-chip-error'");
        $this->addSql("DELETE FROM `background_process` WHERE `name` = 'check-margin-error'");
        $this->addSql("DELETE FROM `background_process` WHERE `name` = 'check-coin-error'");
    }
}
