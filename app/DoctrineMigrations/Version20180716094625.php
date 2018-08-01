<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180716094625 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('571', 'SuiYiYPay', '随意Y付', 'https://gateway.easyipay.com/interface/AutoBank/index.aspx', '0', '', '', '', 'SuiYiYPay', '1', '0', '0', '1', '473', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('571', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('571', '1'), ('571', '3'), ('571', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('571', '1'), ('571', '2'), ('571', '3'), ('571', '4'), ('571', '5'), ('571', '6'), ('571', '8'), ('571', '10'), ('571', '11'), ('571', '12'), ('571', '13'), ('571', '14'), ('571', '15'), ('571', '16'), ('571', '17'), ('571', '278'), ('571', '1092'), ('571', '1098'), ('571', '1103'), ('571', '1104'), ('571', '1107'), ('571', '1108')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('571', 'number', ''), ('571', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('571', '883892677'), ('571', '676547175')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs)
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '571' AND ip IN ('883892677', '676547175')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '571'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '571' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '10', '11', '12', '13', '14', '15', '16', '17', '278', '1092', '1098', '1103', '1104', '1107', '1108')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '571' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '571' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '571'");
    }
}
