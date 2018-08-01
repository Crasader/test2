<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171220034013 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (322, 'YiDauPay', '亿道支付', 'yidpay.com', '1', 'https://query.yidpay.com/query', 'payment.https.query.yidpay.com', '', 'YiDauPay', '1', '0', '0', '1', '225', '1')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('322', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('322', '1'), ('322', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('322', '1'), ('322', '2'), ('322', '3'), ('322', '4'), ('322', '5'), ('322', '6'), ('322', '8'), ('322', '9'), ('322', '10'), ('322', '11'), ('322', '12'), ('322', '13'), ('322', '14'), ('322', '15'), ('322', '16'), ('322', '17'), ('322', '19'), ('322', '220'), ('322', '222'), ('322', '1090')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('322', 'number', ''), ('322', 'private_key', ''), ('322', 'private_key_content', ''), ('322', 'public_key_content', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('322', '3524664194'), ('322', '1902312962'), ('322', '236274674'), ('322', '1959528817')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '322' AND `ip` IN('3524664194', '1902312962', '236274674', '1959528817')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '322'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '322' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '220', '222', '1090')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '322' AND `payment_method_id` IN ('1', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '322'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '322'");
    }
}
