<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180213093758 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('387', 'BeiBeiPay', '貝貝支付', 'beibeihk.com', '1', 'https://query.beibeihk.com/query', 'payment.https.query.beibeihk.com', '', 'BeiBeiPay', '1', '0', '0', '1', '290', '1')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('387', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('387', '1'), ('387', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('387', '1'), ('387', '2'), ('387', '3'), ('387', '4'), ('387', '5'), ('387', '6'), ('387', '8'), ('387', '9'), ('387', '10'), ('387', '11'), ('387', '12'), ('387', '13'), ('387', '14'), ('387', '15'), ('387', '16'), ('387', '17'), ('387', '19'), ('387', '220'), ('387', '222'), ('387', '1103')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('387', 'number', ''), ('387', 'private_key', ''), ('387', 'private_key_content', ''), ('387', 'public_key_content', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('387', '3524664194'), ('387', '1902312962'), ('387', '236274674'), ('387', '1959528817')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '387' AND `ip` IN ('3524664194', '1902312962', '236274674', '1959528817')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '387'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '387' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '220', '222', '1103')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '387' AND `payment_method_id` IN ('1', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '387'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '387'");
    }
}
