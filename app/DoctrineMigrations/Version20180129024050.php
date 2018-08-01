<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180129024050 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('368', 'HiPay', '合付支付', 'http://t.hipay100.com:9001', '0', '', 'payment.http.t.hipay100.com', '', 'HiPay', '1', '0', '0', '1', '271', '1')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('368', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('368', '1'), ('368', '3'), ('368', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('368', '1'), ('368', '2'), ('368', '3'), ('368', '4'), ('368', '5'), ('368', '6'), ('368', '7'), ('368', '8'), ('368', '9'), ('368', '10'), ('368', '11'), ('368', '12'), ('368', '13'), ('368', '15'), ('368', '16'), ('368', '17'), ('368', '222'), ('368', '223'), ('368', '226'), ('368', '228'), ('368', '1090'), ('368', '1092'), ('368', '1093'), ('368', '1097'), ('368', '1103'), ('368', '1104'), ('368', '1107'), ('368', '1109'), ('368', '1111')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('368', 'number', ''), ('368', 'private_key' ,''), ('368', 'private_key_content', ''), ('368', 'public_key_content', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('368', '795095314'), ('368', '795097226')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '368' AND ip IN ('795095314', '795097226')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '368'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '368' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '15', '16', '17', '222', '223', '226', '228', '1090', '1092', '1093', '1097', '1103', '1104', '1107', '1109', '1111')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '368' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '368' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '368'");
    }
}
