<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180205042414 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('375', 'YouMiFu', '優米付', 'http://cashier.youmifu.com/cgi-bin/netpayment/pay_gate.cgi', '0', '', 'payment.http.cashier.youmifu.com', '', 'YouMiFu', '1', '0', '0', '1', '278', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('375', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('375', '1'), ('375', '3'), ('375', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('375', '1'), ('375', '2'), ('375', '3'), ('375', '4'), ('375', '5'), ('375', '6'), ('375', '8'), ('375', '9'), ('375', '10'), ('375', '11'), ('375', '12'), ('375', '13'), ('375', '14'), ('375', '15'), ('375', '16'), ('375', '17'), ('375', '222'), ('375', '1090'), ('375', '1097'), ('375', '1103'), ('375', '1107')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('375', 'number', ''), ('375', 'private_key' ,''), ('375', 'platformID' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('375', '791979283')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '375' AND ip = '791979283'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '375'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '375' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '222', '1090', '1097', '1103', '1107')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '375' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '375' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '375'");
    }
}
