<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180604020649 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('492', 'HuiftPay', '汇付通支付', 'http://www.huiftpay.com/api/pay', '0', '', '', '', 'HuiftPay', '1', '0', '0', '1', '395', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('492', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('492', '1'), ('492', '3'), ('492', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES('492', '1'), ('492', '2'), ('492', '3'), ('492', '4'), ('492', '5'), ('492', '6'), ('492', '8'), ('492', '9'), ('492', '10'), ('492', '11'), ('492', '12'), ('492', '13'), ('492', '14'), ('492', '15'), ('492', '16'), ('492', '17'), ('492', '19'), ('492', '217'), ('492', '221'), ('492', '222'), ('492', '226'), ('492', '278'), ('492', '309'), ('492', '311'), ('492', '1092'), ('492', '1098'), ('492', '1103'), ('492', '1104'), ('492', '1111')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('492', 'number', ''), ('492', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('492', '793453406')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '492' AND `ip` = '793453406'");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '492'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '492' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '221', '222', '226', '278', '309', '311', '1092', '1098', '1103', '1104', '1111')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '492' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '492'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '492'");
    }
}
