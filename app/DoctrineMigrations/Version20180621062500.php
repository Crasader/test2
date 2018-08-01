<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180621062500 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('523', 'HuiLiBaoPay', '汇利宝支付', 'http://47.75.183.85/orderpay.do', '0', '', 'payment.http.47.75.183.85', '', 'HuiLiBaoPay', '1', '0', '0', '1', '425', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('523', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('523', '1'), ('523', '3'), ('523', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('523', '1'), ('523', '3'), ('523', '4'), ('523', '9'), ('523', '11'), ('523', '12'), ('523', '14'), ('523', '16'), ('523', '17'), ('523', '221'), ('523', '222'), ('523', '278'), ('523', '1088'), ('523', '1103'), ('523', '1111'), ('523', '1114')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('523', 'number', ''), ('523', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('523', '793491285')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '523' AND `ip` = '793491285'");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '523'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '523' AND `payment_vendor_id` IN ('1', '3', '4', '9', '11', '12', '14', '16', '17', '221', '222', '278', '1088', '1103', '1111', '1114')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '523' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '523'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '523'");
    }
}
