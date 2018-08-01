<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180416082458 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('437', 'WGPay', 'WG支付', 'http://payapi.459r.com/createOrder.jhtml', '0', '', 'payment.http.payapi.459r.com', '', 'WGPay', '1', '0', '0', '1', '340', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('437', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('437', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('437', '1090')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('437', 'number', ''), ('437', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('437', '2018357917')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '437' AND ip = '2018357917'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '437'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '437' AND payment_vendor_id = '1090'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '437' AND payment_method_id = '8'");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '437' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '437'");
    }
}
