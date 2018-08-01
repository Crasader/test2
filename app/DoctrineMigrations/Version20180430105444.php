<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180430105444 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('453', 'WangLong', '网龙支付', 'http://47.93.11.210/trade/api/ebankPay', '0', '', 'payment.http.47.93.11.210', '', 'WangLong', '1', '0', '0', '1', '356', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('453', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('453', '1'), ('453', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('453', '1'), ('453', '3'), ('453', '4'), ('453', '6'), ('453', '11'), ('453', '16'), ('453', '17'), ('453', '19'), ('453', '1103'), ('453', '1107'), ('453', '1111'), ('453', '1115')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('453', 'number', ''), ('453', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('453', '794627026'), ('453', '795351391'), ('453', '795109943')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs)
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '453' AND ip IN ('794627026', '795351391', '795109943')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '453'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '453' AND payment_vendor_id IN ('1', '3', '4', '6', '11', '16', '17', '19', '1103', '1107', '1111', '1115')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '453' AND payment_method_id IN ('1', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '453' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '453'");
    }
}
