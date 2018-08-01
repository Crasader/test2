<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180621030054 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET post_url = 'http://gateway.clpayment.com/ebank/pay.do' where id = 210");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('210', '1998306422')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('210', '13')");
        $this->addSql("DELETE mlv FROM `merchant_level_vendor` mlv INNER JOIN `merchant` m ON m.`id` = mlv.`merchant_id` WHERE m.`payment_gateway_id` = '210' AND mlv.`payment_vendor_id` IN ('2', '6', '9', '15', '1090', '1092', '1097', '1098', '1103', '1104')");
        $this->addSql("DELETE mlm FROM `merchant_level_method` mlm INNER JOIN `merchant` m ON m.`id` = mlm.`merchant_id` WHERE m.`payment_gateway_id` = '210' AND mlm.`payment_method_id` IN ('3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '210' AND `payment_vendor_id` IN ('2', '6', '9', '15', '1090', '1092', '1097', '1098', '1103', '1104')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '210' AND `payment_method_id` IN ('3', '8')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('210', '3'), ('210', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('210', '2'), ('210', '6'), ('210', '9'), ('210', '15'), ('210', '1090'), ('210', '1092'), ('210', '1097'), ('210', '1098'), ('210', '1103'), ('210', '1104')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '210' AND `payment_vendor_id` = '13'");
        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '210' AND ip = '1998306422'");
        $this->addSql("UPDATE payment_gateway SET post_url = 'zsagepay.com' where id = 210");
    }
}
