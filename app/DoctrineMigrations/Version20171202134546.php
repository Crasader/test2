<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171202134546 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (307, 'JinYang', '金陽支付', 'http://pay.095pay.com/zfapi/order/pay', '0', '', 'payment.http.pay.095pay.com', '', 'JinYang', '1', '0', '0', '1', '210', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('307', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('307', '1'), ('307', '3'), ('307', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('307', '1'), ('307', '2'), ('307', '3'), ('307', '4'), ('307', '5'), ('307', '6'), ('307', '8'), ('307', '9'), ('307', '10'), ('307', '11'), ('307', '12'), ('307', '13'), ('307', '14'), ('307', '15'), ('307', '16'), ('307', '17'), ('307', '19'), ('307', '217'), ('307', '222'), ('307', '223'), ('307', '226'), ('307', '228'), ('307', '233'), ('307', '234'), ('307', '297'), ('307', '1090'), ('307', '1092'), ('307', '1097'), ('307', '1098'), ('307', '1103'), ('307', '1104'), ('307', '1107'), ('307', '1108'), ('307', '1109'), ('307', '1111')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('307', 'number', ''), ('307', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('307', '2018428030'), ('307', '2018428234'), ('307', '2018426398'), ('307', '2018390967'), ('307', '1998011082')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '307' AND `ip` IN ('2018428030', '2018428234', '2018426398', '2018390967', '1998011082')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '307'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '307' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '222', '223', '226', '228', '233', '234', '297', '1090', '1092', '1097', '1098', '1103', '1104', '1107', '1108', '1109', '1111')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '307' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '307'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '307'");
    }
}
