<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180522160648 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('479', 'WanMeiPay', '完美支付', 'http://www.wmpay8.com/pay/paystate/', '0', '', 'payment.http.www.wmpay8.com', '', 'WanMeiPay', '1', '0', '0', '1', '382', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('479', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('479', '3'), ('479', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('479', '1090'), ('479', '1097')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('479', 'number', ''), ('479', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('479', '794365253')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs)
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '479' AND ip = '794365253'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '479'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '479' AND payment_vendor_id IN ('1090', '1097')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '479' AND payment_method_id IN ('3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '479' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '479'");
    }
}