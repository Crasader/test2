<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160725121727 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `background_process` SET `memo` = '轉統計現金全部優惠, 每天12:10' WHERE `name` = 'stat-cash-all-offer'");
        $this->addSql("UPDATE `background_process` SET `memo` = '轉統計現金出入款, 每天12:10' WHERE `name` = 'stat-cash-deposit-withdraw'");
        $this->addSql("UPDATE `background_process` SET `memo` = '轉統計現金優惠, 每天12:10' WHERE `name` = 'stat-cash-offer'");
        $this->addSql("UPDATE `background_process` SET `memo` = '轉統計現金交易明細, 每天12:10' WHERE `name` = 'stat-cash-opcode'");
        $this->addSql("UPDATE `background_process` SET `memo` = '轉統計現金返點, 每天12:10' WHERE `name` = 'stat-cash-rebate'");
        $this->addSql("UPDATE `background_process` SET `memo` = '轉統計現金匯款優惠, 每天12:10' WHERE `name` = 'stat-cash-remit'");
        $this->addSql("UPDATE `background_process` SET `memo` = '轉統計會員現金交易明細, 每天12:10' WHERE `name` = 'stat-domain-cash-opcode'");
        $this->addSql("UPDATE `background_process` SET `memo` = '轉香港時區統計現金交易明細, 每天00:30' WHERE `name` = 'stat-cash-opcode-hk'");
        $this->addSql("UPDATE `background_process` SET `memo` = '轉香港時區統計會員現金交易明細, 每天00:30' WHERE `name` = 'stat-domain-cash-opcode-hk'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `background_process` SET `memo` = '轉統計現金全部優惠, 每天12:00' WHERE `name` = 'stat-cash-all-offer'");
        $this->addSql("UPDATE `background_process` SET `memo` = '轉統計現金出入款, 每天12:00' WHERE `name` = 'stat-cash-deposit-withdraw'");
        $this->addSql("UPDATE `background_process` SET `memo` = '轉統計現金優惠, 每天12:00' WHERE `name` = 'stat-cash-offer'");
        $this->addSql("UPDATE `background_process` SET `memo` = '轉統計現金交易明細, 每天12:00' WHERE `name` = 'stat-cash-opcode'");
        $this->addSql("UPDATE `background_process` SET `memo` = '轉香港時區統計現金交易明細, 每天12:30' WHERE `name` = 'stat-cash-opcode-hk'");
        $this->addSql("UPDATE `background_process` SET `memo` = '轉統計現金返點, 每天12:00' WHERE `name` = 'stat-cash-rebate'");
        $this->addSql("UPDATE `background_process` SET `memo` = '轉統計現金匯款優惠, 每天12:00' WHERE `name` = 'stat-cash-remit'");
        $this->addSql("UPDATE `background_process` SET `memo` = '轉統計會員現金交易明細, 每天12:00' WHERE `name` = 'stat-domain-cash-opcode'");
        $this->addSql("UPDATE `background_process` SET `memo` = '轉香港時區統計會員現金交易明細, 每天12:30' WHERE `name` = 'stat-domain-cash-opcode-hk'");
    }
}
