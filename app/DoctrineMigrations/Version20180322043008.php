<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180322043008 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('416', 'LiDa', '力大支付', 'http://cashier.jlsbank.com/cgi-bin/netpayment/pay_gate.cgi', '0', '', 'payment.https.api.xjockj.com', '', 'LiDa', '1', '0', '0', '1', '319', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('416', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('416', '3')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('416', '1098'), ('416', '1104')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('416', 'number', ''), ('416', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('416', '795083586'), ('416', '795084902'), ('416', '1779304736')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '416' AND ip IN ('795083586', '795084902', '1779304736')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '416'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '416' AND payment_vendor_id IN ('1098', '1104')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '416' AND payment_method_id = '3'");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '416' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '416'");
    }
}

