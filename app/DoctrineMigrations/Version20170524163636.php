<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170524163636 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO `bank_info` (`id`, `bankname`, `virtual`, `withdraw`, `bank_url`, `abbr`, `enable`) VALUES ('425', 'QQ錢包__帳號', '0', '1', 'https://im.qq.com/', 'QQ帳號', '1'), ('426', 'QQ錢包__二維', '0', '1', 'https://im.qq.com/', 'QQ二維', '1'), ('427', '财付通__二維', '0', '1', 'https://www.tenpay.com/', '财付通二維', '1')");
        $this->addSql("INSERT INTO `bank_currency` (`id`, `bank_info_id`, `currency`) VALUES ('413', '425', '156'), ('414', '426', '156'), ('415', '427', '156')");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '支付宝__帳號', `abbr` = '支付宝帳號' WHERE `id` = '281'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '财付通__帳號', `abbr` = '财付通帳號' WHERE `id` = '297'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE `bank_info` SET `bankname` = '财付通', `abbr` = '财付通' WHERE `id` = '297'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '支付宝', `abbr` = '支付宝' WHERE `id` = '281'");
        $this->addSql("DELETE FROM `bank_currency` WHERE `id` IN ('413', '414', '415')");
        $this->addSql("DELETE FROM `bank_info` WHERE `id` IN ('425', '426', '427')");
    }
}
