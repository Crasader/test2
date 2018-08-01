<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180322131518 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('412', 'KuWo', '酷我支付', 'http://saas.yeeyk.com/saas-trx-gateway/order/acceptOrder', '0', '', 'payment.http.saas.yeeyk.com', '', 'KuWo', '1', '0', '0', '1', '315', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('412', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('412', '3'), ('412', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('412', '1097'), ('412', '1103'), ('412', '1104'), ('412', '1111')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('412', 'number', ''), ('412', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('412', '2096833428'), ('412', '2096833429'), ('412', '2096833430'), ('412', '2096833431'), ('412', '2096833432'), ('412', '2096833433'), ('412', '2096833434'), ('412', '2096833435'), ('412', '2096833436'), ('412', '2096833437'), ('412', '2096833438'), ('412', '978553899'), ('412', '3702856131'), ('412', '3702867578'), ('412', '999758170')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs)
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '412' AND ip IN ('2096833428', '2096833429', '2096833430', '2096833431', '2096833432', '2096833433', '2096833434', '2096833435', '2096833436', '2096833437', '2096833438', '978553899', '3702856131', '3702867578', '999758170')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '412'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '412' AND payment_vendor_id IN ('1097', '1103', '1104', '1111')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '412' AND payment_method_id IN ('3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '412' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '412'");
    }
}
