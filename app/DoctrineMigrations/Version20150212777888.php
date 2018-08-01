<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150212777888 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT IGNORE INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `support`, `label`, `bind_ip`, `removed`) VALUES
        (11, 'SZXMOBILE', '易寶手機神州', 'http://58.83.140.10/app-merchant-proxy/node', 0, '', '', '', 0, '', 0, 0),
        (13, 'CNCARD', '雲網', 'https://www.cncard.net/purchase/getorder.asp', 1, 'https://www.cncard.net/purchase/queryorder.asp', '', '', 0, '', 0, 0),
        (14, 'Pay265', 'Pay265', 'https://pay.go2travels.com/DoPayment.aspx', 1, 'http://check.pay265.com/Services/GatewayOrderQuery.asmx/GetOrderByNo', '', '', 0, '', 0, 0),
        (15, 'e6pay', '易信', 'http://www.e6pay.com/Gateway/Gateway.aspx', 0, '', '', '', 0, '', 0, 0),
        (28, 'Xpay', 'Xpay', 'http://www.yes2access.com/Quickpay/TransferBank.aspx', 1, 'http://www.yes2access.com/Quickpay/Webservice/QueryTransaction.asmx/GetTransactionStatus', '', '', 0, '', 0, 0),
        (29, 'iouass', '億網', 'http://www.iouass.com/pay/gateway.asp', 1, 'http://www.iouass.com/pay/getorder.asp', '', '', 0, '', 0, 0),
        (30, 'golpay', '購寶', 'http://pay.golpay.com/app/', 0, '', '', '', 0, '', 0, 0)");

        $this->addSql("INSERT IGNORE INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES (11, 156), (13, 156), (14, 156), (15, 156), (28, 156), (29, 156), (30, 156)");
        $this->addSql("INSERT IGNORE INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES (11, 1), (13, 1), (14, 1), (15, 1), (28, 1), (29, 1), (30, 1)");
        $this->addSql("INSERT IGNORE INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES (11, 213), (13, 1), (13, 2), (13, 3), (13, 4), (13, 5), (13, 6), (13, 8), (13, 9), (13, 10), (13, 11), (13, 12), (13, 13), (13, 17), (14, 1), (14, 2), (14, 3), (14, 4), (14, 5), (14, 17), (15, 1), (15, 2), (15, 3), (15, 4), (15, 5), (15, 6), (15, 8), (15, 10), (15, 11), (15, 12), (15, 14), (15, 16), (15, 17), (28, 1), (28, 2), (28, 3), (28, 4), (28, 5), (28, 6), (28, 8), (28, 9), (28, 10), (28, 11), (28, 12), (28, 13), (28, 14), (28, 15), (28, 16), (28, 17), (28, 217), (28, 219), (28, 226), (28, 227), (28, 228), (28, 231), (28, 233), (29, 1), (29, 3), (29, 4), (29, 5), (29, 6), (29, 8), (29, 10), (29, 11), (29, 12), (29, 13), (29, 14), (29, 16), (29, 17), (30, 1), (30, 2), (30, 3), (30, 4), (30, 5), (30, 6), (30, 8), (30, 9), (30, 10), (30, 12), (30, 14), (30, 15), (30, 16), (30, 17), (30, 19), (30, 217), (30, 221), (30, 222), (30, 223), (30, 233)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` IN ('11', '13', '14', '15', '28', '29', '30')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` IN ('11', '13', '14', '15', '28', '29', '30')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `payment_gateway_id` IN ('11', '13', '14', '15', '28', '29', '30')");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` IN ('11', '13', '14', '15', '28', '29', '30')");
    }
}
