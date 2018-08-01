<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141029233311 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO `bank_info` (`id`, `bankname`, `abbr`, `bank_url`, `virtual`, `withdraw`, `enable`) VALUES ('295', 'OKpay', 'OKpay', 'https://www.okpay.com/', '0', '0', '1')");
        $this->addSql("INSERT INTO `payment_vendor` (`id`, `payment_method_id`, `name`) VALUES ('295', '1', 'OKpay')");
        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `bind_ip`, `support`, `label`) VALUES ('84', 'OKpay', 'OKpay', 'https://www.okpay.com/process.html', '0', '', 'payment.https.www.okpay.com', '172.26.59.2', '0', '1', 'OKpay')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`currency`, `payment_gateway_id`) VALUES ('840', '84')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('84', '1')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor`(`payment_gateway_id`, `payment_vendor_id`) VALUES ('84','295')");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '84' AND `payment_vendor_id` = '295'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '84' AND `payment_method_id` = '1'");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '840' AND `payment_gateway_id` = '84'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '84'");
        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` = '295'");
        $this->addSql("DELETE FROM `bank_info` WHERE `id` = '295'");
    }
}
