<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180315114029 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('408', 'JiuTongPay', '久通支付', 'http://api.jiutongpay.com/api/pay.action', '0', '', 'payment.http.api.jiutongpay.com', '', 'JiuTongPay', '1', '0', '0', '1', '311', '1')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('408', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('408', '1')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('408', '1'), ('408', '2'), ('408', '3'), ('408', '4'), ('408', '5'), ('408', '6'), ('408', '7'), ('408', '8'), ('408', '9'), ('408', '10'), ('408', '11'), ('408', '12'), ('408', '13'), ('408', '14'), ('408', '15'), ('408', '16'), ('408', '17'), ('408', '19'), ('408', '222'), ('408', '228'), ('408', '311')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('408', 'number', ''), ('408', 'private_key', ''), ('408', 'private_key_content', ''), ('408', 'public_key_content', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('408', '2018427564'), ('408', '2018482637'), ('408', '2018453547'), ('408', '2018449670'), ('408', '2018482393')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '408' AND `ip` IN ('2018427564', '2018482637', '2018453547', '2018449670', '2018482393')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '408'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '408' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '222', '228', '311')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '408' AND `payment_method_id` = '1'");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '408'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '408'");
    }
}
