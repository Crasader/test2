<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171203223940 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (309, 'JiXiang', '吉祥付', 'http://118.31.217.126:8081/openapi/pay/cardpay/cardpayapply3', '0', '', 'payment.http.118.31.217.126', '', 'JiXiang', '1', '0', '0', '1', '212', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('309', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('309', '1'), ('309', '3'), ('309', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('309', '1'), ('309', '2'), ('309', '3'), ('309', '4'), ('309', '5'), ('309', '6'), ('309', '8'), ('309', '9'), ('309', '10'), ('309', '11'), ('309', '12'), ('309', '13'), ('309', '14'), ('309', '15'), ('309', '16'), ('309', '17'), ('309', '19'), ('309', '234'), ('309', '1090'), ('309', '1092'), ('309', '1097'), ('309', '1098'), ('309', '1102'), ('309', '1103'), ('309', '1104'), ('309', '1107')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('309', 'number', '商户标识'), ('309', 'private_key', ''), ('309', 'mgroupCode', '平台集团商户编号')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('309', '1950225936'), ('309', '1981798782')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '309' AND `ip` IN ('1950225936', '1981798782')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '309'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '309' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '234', '1090', '1092', '1097', '1098', '1102', '1103', '1104', '1107')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '309' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '309'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '309'");
    }
}
