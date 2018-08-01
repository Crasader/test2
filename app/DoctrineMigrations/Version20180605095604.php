<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180605095604 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('497', 'YinHaoPay', '银号支付', 'http://pay.vbxyz.com/ebank/pay/BgTrans', '0', '', 'payment.http.pay.vbxyz.com', '', 'YinHaoPay', '1', '0', '0', '1', '400', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('497', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('497', '1'), ('497', '3'), ('497', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('497', '1'), ('497', '2'), ('497', '3'), ('497', '4'), ('497', '5'), ('497', '6'), ('497', '8'), ('497', '9'), ('497', '10'), ('497', '11'), ('497', '12'), ('497', '14'), ('497', '15'), ('497', '16'), ('497', '17'), ('497', '1088'), ('497', '1102'), ('497', '1111')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('497', 'number', ''), ('497', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('497', '795520033')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '497' AND `ip` = '795520033'");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '497'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '497' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '14', '15', '16', '17', '1088', '1102', '1111')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '497' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '497'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '497'");
    }
}
