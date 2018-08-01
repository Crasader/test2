<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150611151522 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `bind_ip`, `label`, `removed`) VALUES ('93', 'TongHuiCard', '通匯卡', 'https://pay.41.cn/gateway', '1', 'https://pay.41.cn/query', 'payment.https.pay.41.cn', '172.26.54.2', '0', 'TongHuiCard', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('93', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('93', '1')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('93', '1'), ('93', '2'), ('93', '3'), ('93', '4'), ('93', '5'), ('93', '6'), ('93', '8'), ('93', '11'), ('93', '12'), ('93', '13'), ('93', '14'), ('93', '15'), ('93', '16'), ('93', '17')");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '93' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '11', '12', '13', '14', '15', '16', '17')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '93' AND `payment_method_id` = '1'");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '93'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '93'");
    }
}
