<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171108070143 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (278, 'HuiTongYunFu', '匯通雲付', 'http://www.gtonepay.com/hspay/node/', '0', '', '', '', 'HuiTongYunFu', '1', '0', '0', '1', '182', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('278', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('278', '1'), ('278', '3'), ('278', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('278', '1'), ('278', '2'), ('278', '3'), ('278', '4'), ('278', '5'), ('278', '6'), ('278', '8'), ('278', '9'), ('278', '10'), ('278', '11'), ('278', '12'), ('278', '14'), ('278', '15'), ('278', '16'), ('278', '17'), ('278', '222'), ('278', '223'), ('278', '226'), ('278', '228'), ('278', '1090'), ('278', '1092'), ('278', '1096'), ('278', '1097'), ('278', '1098'), ('278', '1100'), ('278', '1103'), ('278', '1107'), ('278', '1109')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('278', 'number', ''), ('278', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('278', '1740930836')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '278' AND `ip` IN ('1740930836')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '278'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '278' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '14', '15', '16', '17', '222', '223', '226', '228', '1090', '1092', '1096', '1097', '1098', '1100', '1103', '1107', '1109')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '278' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '278'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '278'");
    }
}
