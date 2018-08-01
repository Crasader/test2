<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150420113915 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE background_process ADD last_success DATETIME AFTER `end_at`");
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `memo`, `num`, `msg_num`, `last_success`) VALUES ('stat-cash-opcode', 1, '2015-01-01 00:00:00', '2015-01-01 00:00:00', '轉統計現金交易明細, 每天12:00', 0, 0, '2015-01-01 00:00:00'),('stat-cash-deposit-withdraw', 1, '2015-01-01 00:00:00', '2015-01-01 00:00:00', '轉統計現金出入款, 每天12:00', 0, 0, '2015-01-01 00:00:00'),('stat-cash-offer', 1, '2015-01-01 00:00:00', '2015-01-01 00:00:00', '轉統計現金優惠, 每天12:00', 0, 0, '2015-01-01 00:00:00'),('stat-cash-rebate', 1, '2015-01-01 00:00:00', '2015-01-01 00:00:00', '轉統計現金返點, 每天12:00', 0, 0, '2015-01-01 00:00:00'),('stat-cash-remit', 1, '2015-01-01 00:00:00', '2015-01-01 00:00:00', '轉統計現金匯款優惠, 每天12:00', 0, 0, '2015-01-01 00:00:00'),('stat-cash-all-offer', 1, '2015-01-01 00:00:00', '2015-01-01 00:00:00', '轉統計現金全部優惠, 每天12:00', 0, 0, '2015-01-01 00:00:00')");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE background_process DROP last_success");
        $this->addSql("DELETE FROM background_process WHERE name in ('stat-cash-opcode', 'stat-cash-deposit-withdraw', 'stat-cash-offer', 'stat-cash-rebate', 'stat-cash-remit', 'stat-cash-all-offer')");
    }
}
