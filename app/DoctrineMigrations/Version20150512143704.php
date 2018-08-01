<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150512143704 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql("INSERT INTO `payment_method` (`id`, `name`) VALUES ('6', 'APP支付')");
        $this->addSql("INSERT INTO `payment_vendor` (`id`, `payment_method_id`, `name`) VALUES ('1085', '6', '微支付')");
        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `bind_ip`, `label`, `removed`) VALUES ('92', 'WeiXin', '微信支付', 'https://api.mch.weixin.qq.com/pay/unifiedorder', '1', 'https://api.mch.weixin.qq.com/pay/orderquery', 'payment.https.api.mch.weixin.qq.com', '172.26.54.3', '0', 'WeiXin', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`currency`, `payment_gateway_id`) VALUES ('156', '92')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('92', '6')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor`(`payment_gateway_id`, `payment_vendor_id`) VALUES ('92','1085')");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '92' AND `payment_vendor_id` = '1085'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '92' AND `payment_method_id` = '6'");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '92'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '92'");
        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` = '1085'");
        $this->addSql("DELETE FROM `payment_method` WHERE `id` = '6'");
    }
}
