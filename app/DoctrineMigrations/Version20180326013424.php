<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180326013424 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('419', 'HuiYin', '汇银', 'http://api.huiyin-pay.com/orderpay/pay', '0', '', '', '', 'HuiYin', '1', '0', '0', '1', '322', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('419', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('419', '1'), ('419', '3'), ('419', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('419', '1'), ('419', '2'), ('419', '3'), ('419', '4'), ('419', '5'), ('419', '6'), ('419', '8'), ('419', '9'), ('419', '10'), ('419', '11'), ('419', '12'), ('419', '13'), ('419', '14'), ('419', '15'), ('419', '16'), ('419', '17'), ('419', '19'), ('419', '220'), ('419', '226'), ('419', '278'), ('419', '1088'), ('419', '1090'), ('419', '1092'), ('419', '1097'), ('419', '1098'), ('419', '1103'), ('419', '1104'), ('419', '1107'), ('419', '1111')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('419', 'number', ''), ('419', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('419', '1998335904')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '419' AND ip = '1998335904'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '419'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '419' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '220', '226', '278', '1088', '1090', '1092', '1097', '1098', '1103', '1104', '1107', '1111')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '419' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '419' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '419'");
    }
}
