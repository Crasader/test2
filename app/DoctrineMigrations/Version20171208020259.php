<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171208020259 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (313, 'XunYinPay', '迅銀支付', 'http://pay.xunyinpay.cn/bank/index.aspx', '0', '', '', '', 'XunYinPay', '1', '0', '0', '1', '216', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('313', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('313', '1'), ('313', '3'), ('313', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('313', '1'), ('313', '2'), ('313', '3'), ('313', '4'), ('313', '5'), ('313', '6'), ('313', '7'), ('313', '8'), ('313', '9'), ('313', '10'), ('313', '11'), ('313', '12'), ('313', '13'), ('313', '14'), ('313', '15'), ('313', '16'), ('313', '17'), ('313', '19'), ('313', '217'), ('313', '220'), ('313', '221'), ('313', '223'), ('313', '226'), ('313', '227'), ('313', '228'), ('313', '231'), ('313', '233'), ('313', '234'), ('313', '1090'), ('313', '1092'), ('313', '1097'), ('313', '1098'), ('313', '1103'), ('313', '1104')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('313', 'number', ''), ('313', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('313', '794552089')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '313' AND `ip` = '794552089'");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '313'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '313' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '220', '221', '223', '226', '227', '228', '231', '233', '234', '1090', '1092', '1097', '1098', '1103', '1104')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '313' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '313'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '313'");
    }
}
