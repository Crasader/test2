<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180705140646 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('557', 'UniPayZhiFu', 'UNIPAY支付', 'https://i.ziyezi.com/req', '0', '', 'payment.https.i.ziyezi.com', '', 'UniPayZhiFu', '1', '0', '0', '1', '459', '1')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('557', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('557', '3'), ('557', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('557', '1092'), ('557', '1098'), ('557', '1111'), ('557', '1120')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('557', 'number', ''), ('557', 'private_key', ''), ('557', 'private_key_content', ''), ('557', 'public_key_content', ''), ('557', 'org', ''), ('557', 'AliScanProdId', ''), ('557', 'AliScanSettlePeriod', ''), ('557', 'AliPhoneProdId', ''), ('557', 'AliPhoneSettlePeriod', ''), ('557', 'UnionScanProdId', ''), ('557', 'UnionScanSettlePeriod', ''), ('557', 'UnionPhoneProdId', ''), ('557', 'UnionPhoneSettlePeriod', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('557', '794711632'), ('557', '794783511'), ('557', '794819117')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '557' AND ip IN ('794711632', '794783511', '794819117')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '557'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '557' AND payment_vendor_id IN ('1092', '1098', '1111', '1120')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '557' AND payment_method_id IN ('3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '557' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '557'");
    }
}
