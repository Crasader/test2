<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170707084858 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('193', 'JeanPay', '極付', 'https://pay.jeanpay.com/payment/gateway', '1', 'https://pay.jeanpay.com/payment/gateway', 'payment.https.pay.jeanpay.com', '', 'JeanPay', '0', '0', '0', '1', '98', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('193', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('193', '1'), ('193', '3'), ('193', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('193', '1'), ('193', '2'), ('193', '3'), ('193', '4'), ('193', '5'), ('193', '6'), ('193', '8'), ('193', '9'), ('193', '10'), ('193', '11'), ('193', '12'), ('193', '13'), ('193', '14'), ('193', '15'), ('193', '16'), ('193', '17'), ('193', '19'), ('193', '217'), ('193', '220'), ('193', '221'), ('193', '222'), ('193', '223'), ('193', '226'), ('193', '228'), ('193', '234'), ('193', '278'), ('193', '321'), ('193', '361'), ('193', '1088'), ('193', '1090'), ('193', '1092'), ('193', '1103')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('193', 'number', ''), ('193', 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '193'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '193' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '220', '221', '222', '223', '226', '228', '234', '278', '321', '361', '1088', '1090', '1092', '1103')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '193' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '193'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '193'");
    }
}
