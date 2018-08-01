<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180412110210 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('436', 'TianYiPay', '天奕支付', 'http://pay.vzhipay.com/Pay/GateWayUnionPay.aspx', '0', '', 'payment.http.pay.vzhipay.com', '', 'TianYiPay', '1', '0', '0', '1', '339', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('436', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('436', '1'), ('436', '3'), ('436', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('436', '1'), ('436', '2'), ('436', '3'), ('436', '4'), ('436', '5'), ('436', '6'), ('436', '8'), ('436', '9'), ('436', '10'), ('436', '11'), ('436', '12'), ('436', '13'), ('436', '14'), ('436', '15'), ('436', '16'), ('436', '17'), ('436', '19'), ('436', '221'), ('436', '222'), ('436', '234'), ('436', '1088'), ('436', '1097'), ('436', '1103'), ('436', '1104'), ('436', '1115')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('436', 'number', ''), ('436', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('436', '795493858')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '436' AND ip = '795493858'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '436'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '436' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '221', '222', '234', '1088', '1097', '1103', '1104', '1115')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '436' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '436' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '436'");
    }
}
