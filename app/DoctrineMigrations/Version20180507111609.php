<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180507111609 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('459', 'ManBaPay', '曼巴支付', 'https://api.fzmanba.com/paygateway/mbgateway/gatewayorder/v1', '0', '', 'payment.https.api.fzmanba.com', '', 'ManBaPay', '1', '0', '0', '1', '362', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('459', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('459', '1'), ('459', '3'), ('459', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('459', '1'), ('459', '3'), ('459', '4'), ('459', '6'), ('459', '9'), ('459', '12'), ('459', '16'), ('459', '19'), ('459', '1098'), ('459', '1103'), ('459', '1104')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('459', 'number', ''), ('459', 'private_key' ,''), ('459', 'merAccount' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('459', '2042945122'), ('459', '794822033')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs)
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '459' AND ip IN ('2042945122', '794822033')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '459'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '459' AND payment_vendor_id IN ('1', '3', '4', '6', '9', '12', '16', '19', '1098', '1103', '1104')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '459' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '459' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '459'");
    }
}
