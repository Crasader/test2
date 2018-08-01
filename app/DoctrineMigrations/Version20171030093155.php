<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171030093155 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE `payment_vendor` SET `name` = '微信支付__二维' WHERE `id` = '296'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '微信支付__帐号' WHERE `id` = '313'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '支付宝__二维' WHERE `id` = '314'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '河北银行' WHERE `id` = '315'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '九江银行' WHERE `id` = '317'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '微信支付__二维' WHERE `id` = '1090'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '支付宝__二维' WHERE `id` = '1092'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '财付通_二维' WHERE `id` = '1096'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '微信_手机支付' WHERE `id` = '1097'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '支付宝_手机支付' WHERE `id` = '1098'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '财付通_手机支付' WHERE `id` = '1099'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '收银台' WHERE `id` = '1100'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '支付宝钱包' WHERE `id` = '1101'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '收银台' WHERE `id` = '1102'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = 'QQ_二维' WHERE `id` = '1103'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = 'QQ_手机支付' WHERE `id` = '1104'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '京东钱包__二维' WHERE `id` = '1107'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '京东钱包_手机支付' WHERE `id` = '1108'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '百度钱包__二维' WHERE `id` = '1109'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '百度钱包_手机支付' WHERE `id` = '1110'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '银联钱包__二维' WHERE `id` = '1111'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '支付宝__帐号', `abbr` = '支付宝帐号' WHERE `id` = '281'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '微信支付__二维', `abbr` = '微信二维' WHERE `id` = '296'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '财付通__帐号', `abbr` = '财付通帐号' WHERE `id` = '297'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '微信支付__帐号', `abbr` = '微信帐号' WHERE `id` = '313'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '支付宝__二维', `abbr` = '支付宝二维' WHERE `id` = '314'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '河北银行' WHERE `id` = '315'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '九江银行' WHERE `id` = '317'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = 'QQ钱包__帐号', `abbr` = 'QQ帐号' WHERE `id` = '425'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = 'QQ钱包__二维', `abbr` = 'QQ二维' WHERE `id` = '426'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '财付通__二维', `abbr` = '财付通二维' WHERE `id` = '427'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE `payment_vendor` SET `name` = '微信支付__二維' WHERE `id` = '296'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '微信支付__帳號' WHERE `id` = '313'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '支付宝__二維' WHERE `id` = '314'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '河北銀行' WHERE `id` = '315'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '九江銀行' WHERE `id` = '317'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '微信支付__二維' WHERE `id` = '1090'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '支付宝__二維' WHERE `id` = '1092'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '財付通_二維' WHERE `id` = '1096'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '微信_手機支付' WHERE `id` = '1097'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '支付寶_手機支付' WHERE `id` = '1098'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '財付通_手機支付' WHERE `id` = '1099'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '收銀台' WHERE `id` = '1100'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '支付宝錢包' WHERE `id` = '1101'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '收銀台' WHERE `id` = '1102'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = 'QQ_二維' WHERE `id` = '1103'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = 'QQ_手機支付' WHERE `id` = '1104'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '京東錢包__二維' WHERE `id` = '1107'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '京東錢包_手機支付' WHERE `id` = '1108'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '百度錢包__二維' WHERE `id` = '1109'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '百度錢包_手機支付' WHERE `id` = '1110'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '銀聯錢包__二維' WHERE `id` = '1111'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '支付宝__帳號', `abbr` = '支付宝帳號' WHERE `id` = '281'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '微信支付__二維', `abbr` = '微信二維' WHERE `id` = '296'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '财付通__帳號', `abbr` = '财付通帳號' WHERE `id` = '297'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '微信支付__帳號', `abbr` = '微信帳號' WHERE `id` = '313'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '支付宝__二維', `abbr` = '支付宝二維' WHERE `id` = '314'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '河北銀行' WHERE `id` = '315'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '九江銀行' WHERE `id` = '317'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = 'QQ錢包__帳號', `abbr` = 'QQ帳號' WHERE `id` = '425'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = 'QQ錢包__二維', `abbr` = 'QQ二維' WHERE `id` = '426'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '财付通__二維', `abbr` = '财付通二維' WHERE `id` = '427'");
    }
}
