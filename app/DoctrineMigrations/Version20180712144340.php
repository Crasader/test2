<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180712144340 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('568', 'JiuFangYunPay', '聚方云支付', 'http://mch.hzsbmckj.cn/cloud/cloudplatform/api/trade.html', '0', '', '', '', 'JiuFangYunPay', '1', '0', '0', '1', '470', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('568', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('568', '1'), ('568', '3'), ('568', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('568', '1'), ('568', '3'), ('568', '4'), ('568', '6'), ('568', '9'), ('568', '12'), ('568', '15'), ('568', '16'), ('568', '17'), ('568', '19'), ('568', '1088'), ('568', '1092'), ('568', '1098'), ('568', '1103')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('568', 'number', ''), ('568', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('568', '1883943409')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '568' AND ip = '1883943409'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '568'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '568' AND payment_vendor_id IN ('1', '3', '4', '6', '9', '12', '15', '16', '17', '19', '1088', '1092', '1098', '1103')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '568' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '568' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '568'");
    }
}
