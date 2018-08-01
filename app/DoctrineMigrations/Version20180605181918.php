<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180605181918 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('499', 'XinLuPay', '新陆支付', 'http://47.75.178.180/orderpay.do', '0', '', 'payment.http.47.75.178.180', '', 'XinLuPay', '1', '0', '0', '1', '402', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('499', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('499', '1'), ('499', '3'), ('499', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('499', '1'), ('499', '3'), ('499', '4'), ('499', '9'), ('499', '11'), ('499', '12'), ('499', '14'), ('499', '16'), ('499', '17'), ('499', '221'), ('499', '222'), ('499', '278'), ('499', '1088'), ('499', '1098'), ('499', '1103'), ('499', '1111'), ('499', '1114')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('499', 'number', ''), ('499', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('499', '793490100')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '499' AND `ip` = '793490100'");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '499'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '499' AND `payment_vendor_id` IN ('1', '3', '4', '9', '11', '12', '14', '16', '17', '221', '222', '278', '1088', '1098', '1103', '1111', '1114')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '499' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '499'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '499'");
    }
}
