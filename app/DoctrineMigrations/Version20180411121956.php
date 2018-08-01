<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180411121956 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('433', 'ShouFu', '首付', 'http://a.bzzdp.com/api/createOrder', '0', '', 'payment.http.a.bzzdp.com', '', 'ShouFu', '1', '0', '0', '1', '336', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('433', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('433', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('433', '1103')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('433', 'number', ''), ('433', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('433', '1779340223'), ('433', '1779348909'), ('433', '1779425386')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs)
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '433' AND ip IN ('1779340223', '1779348909', '1779425386')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '433'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '433' AND payment_vendor_id = '1103'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '433' AND payment_method_id = '8'");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '433' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '433'");
    }
}
