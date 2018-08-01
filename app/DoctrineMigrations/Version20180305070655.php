<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180305070655 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('394', 'ChengXinYunPay', '誠信云支付', 'http://cashier.jlsbank.com/cgi-bin/netpayment/pay_gate.cgi', '0', '', '', '', 'ChengXinYunPay', '1', '0', '0', '1', '297', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('394', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('394', '1'), ('394', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('394', '1'), ('394', '2'), ('394', '3'), ('394', '4'), ('394', '5'), ('394', '6'), ('394', '8'), ('394', '9'), ('394', '10'), ('394', '11'), ('394', '12'), ('394', '13'), ('394', '14'), ('394', '15'), ('394', '16'), ('394', '17'), ('394', '222'), ('394', '234'), ('394', '1090'), ('394', '1092'), ('394', '1103')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('394', 'number', ''), ('394', 'private_key' ,''), ('394', 'platformID', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('394', '661445993')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '394' AND ip = '661445993'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '394'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '394' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '222', '234', '1090', '1092', '1103')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '394' AND payment_method_id IN ('1', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '394' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '394'");
    }
}
