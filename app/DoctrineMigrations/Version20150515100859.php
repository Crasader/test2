<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150515100859 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `last_success`, `memo`, `num`, `msg_num`) VALUES ('check-redis-balance', 0, '2015-01-01 00:00:00', '2015-01-01 00:00:00', NULL, '檢查redis餘額是否正確，並刪除不常用redis-key', 0, 0)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM `background_process` WHERE `name` = 'check-redis-balance'");
    }
}
