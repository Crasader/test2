<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171114053409 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('286', 'JiuPaiPay', '九派支付', 'https://jd.kingpass.cn/paygateway/paygateway/bankPayment', '0', '', '', '', 'JiuPaiPay', '1', '0', '0', '1', '190', '1')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('286', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('286', '1')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('286', '1'), ('286', '2'), ('286', '3'), ('286', '4'), ('286', '5'), ('286', '6'), ('286', '8'), ('286', '10'), ('286', '11'), ('286', '12'), ('286', '13'), ('286', '14'), ('286', '15'), ('286', '16'), ('286', '17')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('286', 'number', ''), ('286', 'private_key', ''), ('286', 'private_key_content', ''), ('286', 'public_key_content', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('286', '1920025405'), ('286', '2054369341'), ('286', '2054369511'), ('286', '2054369512'), ('286', '1920025831'), ('286', '1920025832'), ('286', '736333117'), ('286', '736333287'), ('286', '736333288')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '286' AND `ip` IN ('1920025405', '2054369341', '2054369511', '2054369512', '1920025831', '1920025832', '736333117', '736333287', '736333288')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '286'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '286' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '10', '11', '12', '13', '14', '15', '16', '17')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '286' AND `payment_method_id` = '1'");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '286'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '286'");
    }
}
