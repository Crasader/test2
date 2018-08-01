<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180530142647 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('488', 'TianFuPay', '天付支付', 'http://pay.tf767.com/pay/unify', '0', '', 'payment.http.pay.tf767.com', '', 'TianFuPay', '1', '0', '0', '1', '391', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('488', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('488', '3'), ('488', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('488', '1092'), ('488', '1098'), ('488', '1107'), ('488', '1108')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('488', 'number', ''), ('488', 'private_key', ''), ('488', 'private_key_content', ''), ('488', 'public_key_content', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('488', '3054513147'), ('488', '1732660638')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs)
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '488' AND ip IN ('3054513147', '1732660638')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '488'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '488' AND payment_vendor_id IN ('1092', '1098', '1107', '1108')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '488' AND payment_method_id IN ('3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '488' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '488'");
    }
}
