<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180522155157 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `code` = 'QuanTongYunFu', `name` = '全通云付', `post_url` = 'https://www.allpasspay.com/hspay/api_node', `verify_url` = 'payment.https.www.allpasspay.com', `label` = 'QuanTongYunFu' WHERE `id` = '278'");
        $this->addSql("DELETE FROM `payment_gateway_random_float_vendor` WHERE `payment_gateway_id` = '278' AND `payment_vendor_id` IN ('1090', '1097')");
        $this->addSql("DELETE mlv FROM `merchant_level_vendor` mlv INNER JOIN `merchant` m ON m.`id` = mlv.`merchant_id` WHERE m.`payment_gateway_id` = '278' AND mlv.`payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '14', '15', '16', '17', '222', '223', '226', '228', '1090', '1096', '1097', '1100', '1107', '1109')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '278' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '14', '15', '16', '17', '222', '223', '226', '228', '1090', '1096', '1097', '1100', '1107', '1109')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('278', '1111')");
        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '278' AND `ip` = '1740930836'");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('278', '1937641734')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '278' AND `ip` = '1937641734'");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('278', '1740930836')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '278' AND `payment_vendor_id` = '1111'");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('278', '1'), ('278', '2'), ('278', '3'), ('278', '4'), ('278', '5'), ('278', '6'), ('278', '8'), ('278', '9'), ('278', '10'), ('278', '11'), ('278', '12'), ('278', '14'), ('278', '15'), ('278', '16'), ('278', '17'), ('278', '222'), ('278', '223'), ('278', '226'), ('278', '228'), ('278', '1090'), ('278', '1096'), ('278', '1097'), ('278', '1100'), ('278', '1107'), ('278', '1109')");
        $this->addSql("INSERT INTO `payment_gateway_random_float_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('278', '1090'), ('278', '1097')");
        $this->addSql("UPDATE `payment_gateway` SET `code` = 'HuiTongYunFu', `name` = '汇通云付', `post_url` = 'https://www.gtonepay.com/hspay/node/', `verify_url` = '', `label` = 'HuiTongYunFu' WHERE `id` = '278'");
    }
}
