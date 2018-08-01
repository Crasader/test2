<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140909133034 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE `bank_info` SET `bankname` = 'Vietcombank', `abbr` = 'Vietcombank'  WHERE `id` = '237'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = 'TECHCOMBANK', `abbr` = 'TECHCOMBANK' WHERE `id` = '238'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = 'MILITARY BANK', `abbr` = 'MILITARY BANK' WHERE `id` = '239'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = 'VIB BANK', `abbr` = 'VIB BANK' WHERE `id` = '240'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = 'EXIMBANK', `abbr` = 'EXIMBANK' WHERE `id` = '241'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = 'ASIA COMMERICAL BANK', `abbr` = 'ASIA COMMERICAL BANK' WHERE `id` = '242'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = 'NAM A BANK', `abbr` = 'NAM A BANK' WHERE `id` = '243'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = 'MARITIME BANK', `abbr` = 'MARITIME BANK' WHERE `id` = '247'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = 'DONGA BANK', `abbr` = 'DONGA BANK' WHERE `id` = '248'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = 'HDBank', `abbr` = 'HDBank' WHERE `id` = '249'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = 'PG BANK', `abbr` = 'PG BANK' WHERE `id` = '250'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = 'Saigobank', `abbr` = 'Saigobank' WHERE `id` = '251'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = 'NAVIBANK', `abbr` = 'NAVIBANK' WHERE `id` = '252'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = 'Vietcombank' WHERE `id` = '237'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = 'TECHCOMBANK' WHERE `id` = '238'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = 'MILITARY BANK' WHERE `id` = '239'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = 'VIB BANK' WHERE `id` = '240'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = 'EXIMBANK' WHERE `id` = '241'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = 'ASIA COMMERICAL BANK' WHERE `id` = '242'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = 'NAM A BANK' WHERE `id` = '243'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = 'MARITIME BANK' WHERE `id` = '247'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = 'DONGA BANK' WHERE `id` = '248'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = 'HDBank' WHERE `id` = '249'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = 'PG BANK' WHERE `id` = '250'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = 'Saigobank' WHERE `id` = '251'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = 'NAVIBANK' WHERE `id` = '252'");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE `bank_info` SET `bankname` = '宝金银行3', `abbr` = '寶金3' WHERE `id` = '237'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '宝金银行4', `abbr` = '寶金4' WHERE `id` = '238'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '宝金银行5', `abbr` = '寶金5' WHERE `id` = '239'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '宝金银行6', `abbr` = '寶金6' WHERE `id` = '240'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '宝金银行7', `abbr` = '寶金7' WHERE `id` = '241'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '宝金银行8', `abbr` = '寶金8' WHERE `id` = '242'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '宝金银行9', `abbr` = '寶金9' WHERE `id` = '243'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '宝金银行10', `abbr` = '寶金10' WHERE `id` = '247'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '宝金银行11', `abbr` = '寶金11' WHERE `id` = '248'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '宝金银行12', `abbr` = '寶金12' WHERE `id` = '249'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '宝金银行13', `abbr` = '寶金13' WHERE `id` = '250'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '宝金银行14', `abbr` = '寶金14' WHERE `id` = '251'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '宝金银行15', `abbr` = '寶金15' WHERE `id` = '252'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '宝金银行3' WHERE `id` = '237'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '宝金银行4' WHERE `id` = '238'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '宝金银行5' WHERE `id` = '239'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '宝金银行6' WHERE `id` = '240'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '宝金银行7' WHERE `id` = '241'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '宝金银行8' WHERE `id` = '242'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '宝金银行9' WHERE `id` = '243'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '宝金银行10' WHERE `id` = '247'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '宝金银行11' WHERE `id` = '248'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '宝金银行12' WHERE `id` = '249'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '宝金银行13' WHERE `id` = '250'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '宝金银行14' WHERE `id` = '251'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '宝金银行15' WHERE `id` = '252'");
    }
}
