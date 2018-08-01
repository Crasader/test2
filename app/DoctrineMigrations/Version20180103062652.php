<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180103062652 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (335, 'ShangYiZhiFu', '商易付支付', 'https://gateway.shangyizhifu.com/chargebank.aspx', '0', '', 'payment.https.gateway.shangyizhifu.com', '', 'ShangYiZhiFu', '1', '0', '0', '1', '238', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES (335, 156)");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('335', '1'), ('335', '3'), ('335', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('335', '1'), ('335', '2'), ('335', '3'), ('335', '4'), ('335', '5'), ('335', '6'), ('335', '8'), ('335', '9'), ('335', '10'), ('335', '11'), ('335', '12'), ('335', '13'), ('335', '14'), ('335', '15'), ('335', '16'), ('335', '17'), ('335', '19'), ('335', '1090'), ('335', '1092'), ('335', '1097'), ('335', '1103'), ('335', '1107'), ('335', '1111')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('335', 'number', ''), ('335', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('335', '1742340241')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '335' AND `ip` = '1742340241'");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '335'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '335' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '1090', '1092', '1097', '1103', '1107', '1111')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '335' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '335'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '335'");
    }
}
