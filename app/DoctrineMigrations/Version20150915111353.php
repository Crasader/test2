<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150915111353 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`) VALUES (96, 'BBPay', '幣幣支付', 'http://api.bbpay.com/bbpayapi/api/pcpay/merpay', 1, 'http://api.bbpay.com/bbpayapi/api/query/queryOrder', 'payment.http.api.bbpay.com', '172.26.54.3', 'BBPay', 0, 0)");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES (96, 156)");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('96', '1')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('96', '1'), ('96', '2'), ('96', '3'), ('96', '4'), ('96', '5'), ('96', '6'), ('96', '8'), ('96', '9'), ('96', '11'), ('96', '12'), ('96', '13'), ('96', '14'), ('96', '15'), ('96', '16'), ('96', '17'), ('96', '19'), ('96', '217'), ('96', '220'), ('96', '221'), ('96', '226'), ('96', '234')");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '96' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '11', '12', '13', '14', '15', '16', '17', '19', '217', '220', '221', '226', '234')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '96' AND `payment_method_id` = '1'");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '96'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '96'");
    }
}
