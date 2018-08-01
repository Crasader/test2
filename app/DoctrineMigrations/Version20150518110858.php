<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150518110858 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO `bank_info` (`id`, `bankname`, `abbr`, `bank_url`, `virtual`, `withdraw`, `enable`) VALUES ('298', 'Bank Central Asia', 'BCA', 'http://www.bca.co.id/en/individual/individual.jsp', '0', '1', '1')");
        $this->addSql("INSERT INTO `payment_vendor` (`id`, `payment_method_id`, `name`) VALUES ('298', '1', 'Bank Central Asia')");
        $this->addSql("INSERT INTO `bank_currency` (`id`, `bank_info_id`, `currency`) VALUES ('286', '298', '360')");

        $this->addSql("INSERT INTO `bank_info` (`id`, `bankname`, `abbr`, `bank_url`, `virtual`, `withdraw`, `enable`) VALUES ('299', 'Bank Mandiri', 'Mandiri', 'http://www.bankmandiri.co.id/english/index.aspx', '0', '1', '1')");
        $this->addSql("INSERT INTO `payment_vendor` (`id`, `payment_method_id`, `name`) VALUES ('299', '1', 'Bank Mandiri')");
        $this->addSql("INSERT INTO `bank_currency` (`id`, `bank_info_id`, `currency`) VALUES ('287', '299', '360')");

        $this->addSql("INSERT INTO `bank_info` (`id`, `bankname`, `abbr`, `bank_url`, `virtual`, `withdraw`, `enable`) VALUES ('300', 'Bank Negara Indonesia', 'BNI', 'http://bni.co.id/id-id/Beranda.aspx', '0', '1', '1')");
        $this->addSql("INSERT INTO `payment_vendor` (`id`, `payment_method_id`, `name`) VALUES ('300', '1', 'Bank Negara Indonesia')");
        $this->addSql("INSERT INTO `bank_currency` (`id`, `bank_info_id`, `currency`) VALUES ('288', '300', '360')");

        $this->addSql("INSERT INTO `bank_info` (`id`, `bankname`, `abbr`, `bank_url`, `virtual`, `withdraw`, `enable`) VALUES ('301', 'Bank Rakyat Indonesia', 'BRI', 'https://ib.bri.co.id/', '0', '1', '1')");
        $this->addSql("INSERT INTO `payment_vendor` (`id`, `payment_method_id`, `name`) VALUES ('301', '1', 'Bank Rakyat Indonesia')");
        $this->addSql("INSERT INTO `bank_currency` (`id`, `bank_info_id`, `currency`) VALUES ('289', '301', '360')");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM `bank_currency` WHERE `id` = '286'");
        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` = '298'");
        $this->addSql("DELETE FROM `bank_info` WHERE `id` = '298'");

        $this->addSql("DELETE FROM `bank_currency` WHERE `id` = '287'");
        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` = '299'");
        $this->addSql("DELETE FROM `bank_info` WHERE `id` = '299'");

        $this->addSql("DELETE FROM `bank_currency` WHERE `id` = '288'");
        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` = '300'");
        $this->addSql("DELETE FROM `bank_info` WHERE `id` = '300'");

        $this->addSql("DELETE FROM `bank_currency` WHERE `id` = '289'");
        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` = '301'");
        $this->addSql("DELETE FROM `bank_info` WHERE `id` = '301'");
    }
}
