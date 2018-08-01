<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180417083654 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('443', 'XunJeiPay', '迅捷付', 'http://pay88.cat39.com/trans/trans/api/back.json', '0', '', 'payment.http.pay88.cat39.com', '', 'XunJeiPay', '1', '0', '0', '1', '346', '1')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('443', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('443', '1'), ('443', '3')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('443', '1'), ('443', '2'), ('443', '3'), ('443', '4'), ('443', '5'), ('443', '6'), ('443', '9'), ('443', '12'), ('443', '14'), ('443', '16'), ('443', '17'), ('443', '19'), ('443', '278'), ('443', '1088')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('443', 'number', ''), ('443', 'private_key', ''), ('443', 'private_key_content', ''), ('443', 'public_key_content', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('443', '1740746119')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '443' AND `ip` = '1740746119'");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '443'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '443' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '9', '12', '14', '16', '17', '19', '278', '1088')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '443' AND `payment_method_id` IN ('1', '3')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '443'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '443'");
    }
}
