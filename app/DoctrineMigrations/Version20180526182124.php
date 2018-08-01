<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180526182124 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('489', 'XinShengPay', '新生宝', 'xinshengpay.cc', '1', '', '', '', 'XinShengPay', '1', '0', '0', '1', '392', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('489', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('489', '1'), ('489', '3'), ('489', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('489', '1'), ('489', '2'), ('489', '3'), ('489', '4'), ('489', '5'), ('489', '6'), ('489', '8'), ('489', '9'), ('489', '10'), ('489', '11'), ('489', '12'), ('489', '13'), ('489', '14'), ('489', '15'), ('489', '16'), ('489', '17'), ('489', '19'), ('489', '278'), ('489', '1088'), ('489', '1090'), ('489', '1092'), ('489', '1103'), ('489', '1107'), ('489', '1109'), ('489', '1111')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('489', 'number', ''), ('489', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('489', '2345079387'), ('489', '2077191065')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '489' AND ip IN ('2345079387', '2077191065')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '489'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '489' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '278', '1088', '1090', '1092', '1103', '1107', '1109', '1111')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '489' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '489' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '489'");
    }
}
