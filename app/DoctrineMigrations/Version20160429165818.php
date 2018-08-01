<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160429165818 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (103, 'NewDinPayRsaS', '新快匯寶RSA-S支付', 'https://pay.dinpay.com/gateway?input_charset=UTF-8', '1', 'https://query.dinpay.com/query', 'payment.https.query.dinpay.com', '172.26.54.42', 'NewDinPayRsaS', '0', '0', '0', '1', '0', '1')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES (103, 156)");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('103', '1'), ('103', '2')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id` ,`payment_vendor_id`) VALUES ('103', '1'), ('103', '2'), ('103', '3'), ('103', '4'), ('103', '5'), ('103', '6'), ('103', '8'), ('103', '9'), ('103', '10'), ('103', '11'), ('103', '12'), ('103', '13'), ('103', '14'), ('103', '15'), ('103', '16'), ('103', '17'), ('103', '19'), ('103', '222'), ('103', '1000'), ('103', '1001'), ('103', '1002'), ('103', '1073'), ('103', '1075'), ('103', '1076'), ('103', '1077'), ('103', '1078'), ('103', '1079'), ('103', '1080'), ('103', '1082'), ('103', '1083'), ('103', '1086'), ('103', '1087')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '103' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '222', '1000', '1001', '1002', '1073', '1075', '1076', '1077', '1078', '1079', '1080', '1082', '1083', '1086', '1087')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '103' AND `payment_method_id` IN ('1', '2')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '103'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '103'");
    }
}
