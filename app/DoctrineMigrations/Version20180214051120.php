<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180214051120 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('389', 'XinJuYiYunPay', '新聚易云支付', 'http://47.95.45.37/pay.php', '0', '', '', '', 'XinJuYiYunPay', '1', '0', '0', '1', '292', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('389', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('389', '1')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('389', '1'), ('389', '2'), ('389', '3'), ('389', '4'), ('389', '5'), ('389', '6'), ('389', '8'), ('389', '10'), ('389', '11'), ('389', '12'), ('389', '13'), ('389', '14'), ('389', '15'), ('389', '16'), ('389', '17'), ('389', '19'), ('389', '223')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('389', 'number', ''), ('389', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('389', '794815219')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '389' AND ip = '794815219'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '389'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '389' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '10', '11', '12', '13', '14', '15', '16', '17', '19', '223')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '389' AND payment_method_id = '1'");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '389' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '389'");
    }
}
