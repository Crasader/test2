<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180703063120 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('550', 'MinLePay', '民乐支付', 'https://www.minlepay.com/api/pay', '0', '', '', '', 'MinLePay', '1', '0', '0', '1', '452', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('550', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('550', '1'), ('550', '3'), ('550', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('550', '1'), ('550', '2'), ('550', '3'), ('550', '4'), ('550', '5'), ('550', '6'), ('550', '8'), ('550', '9'), ('550', '10'), ('550', '11'), ('550', '12'), ('550', '13'), ('550', '14'), ('550', '15'), ('550', '16'), ('550', '17'), ('550', '19'), ('550', '234'), ('550', '278'), ('550', '1088'), ('550', '1092'), ('550', '1098')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('550', 'number', ''), ('550', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('550', '1846194737')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '550' AND ip = '1846194737'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '550'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '550' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '234', '278', '1088', '1092', '1098')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '550' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '550' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '550'");
    }
}
