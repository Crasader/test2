<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180614145528 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('505', 'ShanFuTongPay', '闪付通支付', 'http://www.xgpay.cc/api/trans/pay', '0', '', 'payment.http.www.xgpay.cc', '', 'ShanFuTongPay', '1', '0', '0', '1', '407', '1')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('505', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('505', '1'), ('505', '3'), ('505', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('505', '1'), ('505', '2'), ('505', '3'), ('505', '4'), ('505', '5'), ('505', '6'), ('505', '8'), ('505', '10'), ('505', '11'), ('505', '12'), ('505', '13'), ('505', '14'), ('505', '16'), ('505', '17'), ('505', '217'), ('505', '223'), ('505', '308'), ('505', '311'), ('505', '1098'), ('505', '1103'), ('505', '1111')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('505', 'number', ''), ('505', 'private_key', ''), ('505', 'private_key_content', ''), ('505', 'public_key_content', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('505', '791954948')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs)
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '505' AND ip = '791954948'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '505'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '505' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '10', '11', '12', '13', '14', '16', '17', '217', '223', '308', '311', '1098', '1103', '1111')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '505' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '505' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '505'");
    }
}
