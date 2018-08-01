<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180620074105 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('520', 'HfbPay', '随意付', 'http://sytrade.fjjxjj.com/cgi-bin/netpayment/pay_gate.cgi', '0', '', '', '', 'HfbPay', '1', '0', '0', '1', '422', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('520', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('520', '1')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('520', '1'), ('520', '2'), ('520', '3'), ('520', '4'), ('520', '5'), ('520', '6'), ('520', '8'), ('520', '10'), ('520', '11'), ('520', '12'), ('520', '13'), ('520', '14'), ('520', '15'), ('520', '16'), ('520', '17')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('520', 'number', ''), ('520', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('520', '1952282845')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '520' AND ip = '1952282845'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '520'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '520' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '10', '11', '12', '13', '14', '15', '16', '17')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '520' AND payment_method_id = '1'");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '520' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '520'");
    }
}
