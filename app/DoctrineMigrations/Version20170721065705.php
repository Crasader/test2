<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170721065705 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_vendor` (`id`, `payment_method_id`, `name`) VALUES ('1111', '8', '銀聯錢包__二維')");
        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('196', 'Pay35', '35支付', 'https://gw.555pay.com/native/com.opentech.cloud.pay.trade.create/1.0.0', '1', 'https://gw.555pay.com/native/com.opentech.cloud.pay.trade.query/1.0.0', 'payment.https.gw.555pay.com', '', 'Pay35', '0', '0', '0', '1', '101', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('196', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('196', '1'),('196', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('196', '1'), ('196', '2'), ('196', '3'), ('196', '4'), ('196', '5'), ('196', '6'), ('196', '8'), ('196', '9'), ('196', '10'), ('196', '11'), ('196', '12'), ('196', '13'), ('196', '14'), ('196', '15'), ('196', '16'), ('196', '17'), ('196', '220'), ('196', '222'), ('196', '226'), ('196', '234'), ('196', '1090'), ('196', '1092'), ('196', '1103'), ('196', '1111')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('196', 'number', ''), ('196', 'private_key', ''), ('196', 'merchantCerNo', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '196'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '196' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '220', '222', '226', '234','1090', '1092', '1103', '1111')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '196' AND `payment_method_id` IN ('1', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '196'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '196'");
        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` = '1111'");
    }
}
