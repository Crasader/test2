<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160812103803 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE merchant CHANGE private_key private_key VARCHAR(1024) NOT NULL');
        $this->addSql('ALTER TABLE merchant_card CHANGE private_key private_key VARCHAR(1024) NOT NULL');
        $this->addSql('ALTER TABLE merchant_withdraw CHANGE private_key private_key VARCHAR(1024) NOT NULL');
        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (120, 'FunPay', '樂盈支付', 'https://www.funpay.com/website/pay.htm', '1', 'https://www.funpay.com/website/queryOrderResult.htm', 'payment.https.www.funpay.com', '', 'FunPay', '0', '0', '0', '1', '29', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES (120, 156)");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('120', '1'), (120, 8)");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('120', '1'), ('120', '2'), ('120', '3'), ('120', '4'), ('120', '5'), ('120', '6'), ('120', '7'), ('120', '8'), ('120', '9'), ('120', '10'), ('120', '11'), ('120', '12'), ('120', '13'), ('120', '14'), ('120', '15'), ('120', '16'), ('120', '17'), ('120', '222'), ('120', '223'), ('120', '1090')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('120', 'number', ''), ('120', 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '120'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '120' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '222', '223', '1090')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '120' AND `payment_method_id` IN ('1', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '120'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '120'");
        $this->addSql('ALTER TABLE merchant_withdraw CHANGE private_key private_key VARCHAR(512) NOT NULL');
        $this->addSql('ALTER TABLE merchant CHANGE private_key private_key VARCHAR(512) NOT NULL');
        $this->addSql('ALTER TABLE merchant_card CHANGE private_key private_key VARCHAR(512) NOT NULL');
    }
}
