<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150323184307 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `bind_ip`, `label`, `removed`) VALUES ('88', 'MoBaoPay', '摩宝支付', 'https://trade.mobaopay.com/cgi-bin/netpayment/pay_gate.cgi', '1', 'https://trade.mobaopay.com/cgi-bin/netpayment/pay_gate.cgi', 'payment.https.trade.mobaopay.com', '172.26.54.2', '0', 'MoBaoPay', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('88', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('88', '1')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('88', '1'), ('88', '2'), ('88', '3'), ('88', '4'), ('88', '5'), ('88', '6'), ('88', '8'), ('88', '10'), ('88', '11'), ('88', '12'), ('88', '13'), ('88', '14'), ('88', '15'), ('88', '16'), ('88', '17')");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '88' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '10', '11', '12', '13', '14', '15', '16', '17')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '88' AND `payment_method_id` = '1'");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '88'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '88'");
    }
}
