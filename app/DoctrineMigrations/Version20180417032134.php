<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180417032134 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('441', 'CGPay', 'CGPay', 'http://public.meowpay.io/api/v1/BuildGlobalPayOrder', '0', '', 'payment.http.public.meowpay.io', '', 'CGPay', '1', '0', '0', '1', '344', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('441', '156')");
        $this->addSql("INSERT INTO payment_vendor (id, payment_method_id, name, version) VALUES ('1117', '8', 'CG钱包', '1')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('441', '1117')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('441', 'number', ''), ('441', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('441', '2321224114'), ('441', '2321224129'), ('441', '2321224165'), ('441', '2321223960')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '441' AND ip IN ('2321224114', '2321224129', '2321224165', '2321223960')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '441'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '441' AND payment_vendor_id = '1117'");
        $this->addSql("DELETE FROM payment_vendor WHERE id = '1117'");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '441' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '441'");
    }
}
