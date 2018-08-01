<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180628070323 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('541', 'TongYinPay', '同银支付', 'http://47.52.155.221:18888/open-gateway/trade/invoke ', '0', '', 'payment.http.47.52.155.221', '', 'TongYinPay', '1', '0', '0', '1', '443', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('541', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('541', '1'), ('541', '3'), ('541', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('541', '1'), ('541', '3'), ('541', '4'), ('541', '5'), ('541', '8'), ('541', '10'), ('541', '11'), ('541', '12'), ('541', '13'), ('541', '14'), ('541', '16'), ('541', '17'), ('541', '1092'), ('541', '1098')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('541', 'number', ''), ('541', 'private_key', ''), ('478', 'orgId', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('541', '791976925')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '541' AND `ip` = '791976925'");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '541'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '541' AND `payment_vendor_id` IN ('1', '3', '4', '5', '8', '10', '11', '12', '13', '14', '16', '17', '1092', '1098')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '541' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '541'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '541'");
    }
}
