<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161117164323 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (142, 'KdPay', '口袋支付', 'http://api.duqee.com/pay/Bank.aspx', 1, 'http://api.duqee.com/pay/query.aspx', 'payment.http.api.duqee.com', '', 'KdPay', 0, 0, 0, 1, 49, 0)");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES (142, 156)");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES (142, 1), (142, 8)");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES (142, 1), (142, 2), (142, 3), (142, 4), (142, 5), (142, 6), (142, 7), (142, 8), (142, 9), (142, 10), (142, 11), (142, 12), (142, 13), (142, 14), (142, 15), (142, 16), (142, 17), (142, 19), (142, 217), (142, 220), (142, 221), (142, 222), (142, 223), (142, 226), (142, 228), (142, 233), (142, 234), (142, 1090)");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES (142, 'number', ''), (142, 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = 142");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = 142 AND `payment_vendor_id` IN (1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 19, 217, 220, 221, 222, 223, 226, 228, 233, 234, 1090)");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = 142 AND `payment_method_id` IN (1, 8)");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = 156 AND `payment_gateway_id` = 142");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = 142");
    }
}
