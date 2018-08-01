<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171214042313 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (318, 'ZFTong', '支付通', 'https://ebank.ztpo.cn/payment/v1/order/', '0', '', 'payment.https.ebank.ztpo.cn', '', 'ZFTong', '1', '0', '0', '1', '221', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('318', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('318', '1'), ('318', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('318', '1'), ('318', '2'), ('318', '3'), ('318', '4'), ('318', '5'), ('318', '6'), ('318', '8'), ('318', '9'), ('318', '10'), ('318', '11'), ('318', '12'), ('318', '13'), ('318', '14'), ('318', '15'), ('318', '16'), ('318', '17'), ('318', '19'), ('318', '278'), ('318', '1090'), ('318', '1092'), ('318', '1103'), ('318', '1107')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('318', 'number', ''), ('318', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('318', '1903261914'), ('318', '1903261916'), ('318', '1903261921')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '318' AND `ip` IN ('1903261914', '1903261916', '1903261921')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '318'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '318' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '278', '1090', '1092', '1103', '1107')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '318' AND `payment_method_id` IN ('1', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '318'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '318'");
    }
}
