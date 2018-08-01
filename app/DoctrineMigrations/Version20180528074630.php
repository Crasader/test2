<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180528074630 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'http://103.84.47.121:8080/YBT/YBTPAY', `verify_url` = '',  `upload_key` = 0, `auto_reop` = 0 WHERE `id` = '392'");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('392', '3')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('392', '1'), ('392', '2'), ('392', '3'), ('392', '4'), ('392', '5'), ('392', '6'), ('392', '13'), ('392', '15'), ('392', '16'), ('392', '17'), ('392', '19'), ('392', '309'), ('392', '311'), ('392', '1098')");
        $this->addSql("DELETE mlv FROM `merchant_level_vendor` mlv INNER JOIN `merchant` m ON m.`id` = mlv.`merchant_id` WHERE m.`payment_gateway_id` = '392' AND mlv.`payment_vendor_id` = '1102'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '392' AND `payment_vendor_id` = '1102'");
        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '392' AND `ip` IN ('3524664194', '1902312962', '236274674', '1959528817')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('392', '1733570425')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '392' AND `ip` = '1733570425'");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('392', '3524664194'), ('392', '1902312962'), ('392', '236274674'), ('392', '1959528817')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('392', '1102')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '392' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '13', '15', '16', '17', '19', '309', '311', '1098')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '392' AND payment_method_id = '3'");
        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'https://pay.boxiantech.com/gateway?input_charset=UTF-8', `verify_url` = 'payment.https.query.boxiantech.com',  `upload_key` = 1, `auto_reop` = 1 WHERE `id` = '392'");
    }
}
