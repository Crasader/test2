<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180620040654 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('518', 'QuanFuTong', '全富通支付', 'https://api.yingyupay.com:31006/yypay', '0', '', 'payment.https.api.yingyupay.com', '', 'QuanFuTong', '1', '0', '0', '1', '420', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('518', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('518', '1'), ('518', '3'), ('518', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('518', '1'), ('518', '2'), ('518', '3'), ('518', '4'), ('518', '5'), ('518', '6'), ('518', '8'), ('518', '9'), ('518', '10'), ('518', '11'), ('518', '12'), ('518', '13'), ('518', '14'), ('518', '15'), ('518', '16'), ('518', '17'), ('518', '19'), ('518', '228'), ('518', '234'), ('518', '1088'), ('518', '1090'), ('518', '1092'), ('518', '1097'), ('518', '1098'), ('518', '1103'), ('518', '1104'), ('518', '1107'), ('518', '1108'), ('518', '1111')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('518', 'number', ''), ('518', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('518', '1991797066')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '518' AND ip = '1991797066'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '518'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '518' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '228', '234', '1088', '1090', '1092', '1097', '1098', '1103', '1104', '1107', '1108', '1111')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '518' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '518' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '518'");
    }
}
