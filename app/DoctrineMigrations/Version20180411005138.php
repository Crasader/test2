<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180411005138 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('431', 'YiShengFu', '亿胜付', 'http://cashier.zgmyb.top/cgi-bin/netpayment/pay_gate.cgi', '0', '', 'payment.http.cashier.zgmyb.top', '', 'YiShengFu', '1', '0', '0', '1', '334', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('431', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('431', '1'), ('431', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('431', '1'), ('431', '2'), ('431', '3'), ('431', '4'), ('431', '5'), ('431', '6'), ('431', '8'), ('431', '9'), ('431', '10'), ('431', '11'), ('431', '12'), ('431', '13'), ('431', '14'), ('431', '15'), ('431', '16'), ('431', '17'), ('431', '19'), ('431', '217'), ('431', '221'), ('431', '223'), ('431', '228'), ('431', '234'), ('431', '311'), ('431', '312'), ('431', '361'), ('431', '1103'), ('431', '1107')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('431', 'number', ''), ('431', 'private_key' ,''), ('431', 'platformID', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('431', '1950261637')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '431' AND ip = '1950261637'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '431'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '431' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '221', '223', '228', '234', '311', '312', '361', '1103', '1107')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '431' AND payment_method_id IN ('1', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '431' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '431'");
    }
}
