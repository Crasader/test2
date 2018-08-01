<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180411161014 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('435', 'HYuanPay', '寰源支付', 'http://atla.atlashyuan.com/online/gateway', '0', '', 'payment.http.atla.atlashyuan.com', '', 'HYuanPay', '1', '0', '0', '1', '338', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('435', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('435', '1'), ('435', '3'), ('435', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('435', '1'), ('435', '2'), ('435', '3'), ('435', '4'), ('435', '5'), ('435', '6'), ('435', '8'), ('435', '9'), ('435', '10'), ('435', '11'), ('435', '12'), ('435', '13'), ('435', '14'), ('435', '15'), ('435', '16'), ('435', '17'), ('435', '19'), ('435', '217'), ('435', '222'), ('435', '223'), ('435', '226'), ('435', '228'), ('435', '233'), ('435', '234'), ('435', '278'), ('435', '1088'), ('435', '1103'), ('435', '1104')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('435', 'number', ''), ('435', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('435', '2472292147')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '435' AND `ip` = '2472292147'");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '435'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '435' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '222', '223', '226', '228', '233', '234', '278', '1088', '1103', '1104')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '435' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '435'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '435'");
    }
}
