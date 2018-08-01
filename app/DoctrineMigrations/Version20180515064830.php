<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180515064830 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('471', 'AIPay', 'AI支付', 'https://pay.all-inpay.com/gateway/pay.jsp', '0', '', 'payment.https.pay.all-inpay.com', '', 'AIPay', '1', '0', '0', '1', '374', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('471', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('471', '1'), ('471', '3'), ('471', '8')");
        $this->addSql("INSERT INTO `payment_vendor` (id, payment_method_id, name, version) VALUES ('1119', '3', '支付宝条码_手机支付', '1')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('471', '1'), ('471', '2'), ('471', '3'), ('471', '4'), ('471', '5'), ('471', '6'), ('471', '8'), ('471', '9'), ('471', '10'), ('471', '11'), ('471', '12'), ('471', '13'), ('471', '14'), ('471', '15'), ('471', '16'), ('471', '17'), ('471', '19'), ('471', '1092'), ('471', '1103'), ('471', '1111'), ('471', '1119')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('471', 'number', ''), ('471', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('471', '1742192546'), ('471', '2043216053')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '471' AND `ip`  IN ('1742192546', '2043216053')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '471'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '471' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '1092', '1103', '1111', '1119')");
        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` = '1119'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '471' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '471'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '471'");
    }
}
