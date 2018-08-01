<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180621200219 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `code` = 'DongNanPay', `name` = '东南支付', `post_url` = 'payment.https.api.ttkag.com', `reop_url` = 'https://query.ttkag.com/query', `verify_url` = 'payment.https.query.ttkag.com', `label` = 'DongNanPay' WHERE `id` = '387'");
        $this->addSql("DELETE mlv FROM `merchant_level_vendor` mlv JOIN `merchant` m ON m.`id` = mlv.`merchant_id` WHERE m.`payment_gateway_id` = '387' AND mlv.`payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '220', '222', '1103')");
        $this->addSql("DELETE mlm FROM `merchant_level_method` mlm JOIN `merchant` m ON m.`id` = mlm.`merchant_id` WHERE m.`payment_gateway_id` = '387' AND mlm.`payment_method_id` = '1'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '387' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '220', '222', '1103')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '387' AND `payment_method_id` = '1'");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('387', '1032711152'), ('387', '1959528810'), ('387', '2043237741')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '387' AND ip IN ('1032711152', '1959528810', '2043237741')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('387', '1')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('387', '1'), ('387', '2'), ('387', '3'), ('387', '4'), ('387', '5'), ('387', '6'), ('387', '8'), ('387', '9'), ('387', '10'), ('387', '11'), ('387', '12'), ('387', '13'), ('387', '14'), ('387', '15'), ('387', '16'), ('387', '17'), ('387', '19'), ('387', '220'), ('387', '222'), ('387', '1103')");
        $this->addSql("UPDATE `payment_gateway` SET `code` = 'BeiBeiPay', `name` = '贝贝支付', `post_url` = 'ttkag.com', `reop_url` = 'https://query.beibeihk.com/query', `verify_url` = 'payment.https.query.beibeihk.com', `label` = 'BeiBeiPay' WHERE `id` = '387'");
    }
}
