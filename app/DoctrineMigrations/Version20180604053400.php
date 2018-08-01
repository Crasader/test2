<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180604053400 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'http://pay.leefupay.com/pay/create/order', `auto_reop` = '0', `reop_url` = '', `verify_url` = 'payment.http.pay.leefupay.com', `bind_ip` = '1', `hot` = '1' WHERE `id` = '98'");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('98', '3'), ('98', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('98', '1090'), ('98', '1097')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('98', '791974531')");
        $this->addSql("DELETE mlv FROM `merchant_level_vendor` mlv INNER JOIN `merchant` m ON m.`id` = mlv.`merchant_id` WHERE m.`payment_gateway_id` = '98' AND mlv.`payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '222', '223', '226')");
        $this->addSql("DELETE mlm FROM `merchant_level_method` mlm INNER JOIN `merchant` m ON m.`id` = mlm.`merchant_id` WHERE m.`payment_gateway_id` = '98' AND mlm.`payment_method_id` = '1'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '98' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '222', '223', '226')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '98' AND `payment_method_id` = '1'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('98', '1')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('98', '1'), ('98', '2'), ('98', '3'), ('98', '4'), ('98', '5'), ('98', '6'), ('98', '8'), ('98', '9'), ('98', '10'), ('98', '11'), ('98', '12'), ('98', '13'), ('98', '14'), ('98', '15'), ('98', '16'), ('98', '17'), ('98', '19'), ('98', '222'), ('98', '223'), ('98', '226')");
        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '98' AND `ip` = '791974531'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '98' AND `payment_vendor_id` IN ('1090', '1097')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '98' AND `payment_method_id` IN ('3', '8')");
        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'http://pay.lefu8.com/gateway/trade.htm', `auto_reop` = '1', `reop_url` = 'https://pay.lefu8.com/gateway/query.htm', `verify_url` = 'payment.https.pay.lefu8.com', `bind_ip` = '0', `hot` = '0' WHERE `id` = '98'");
    }
}
