<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180509074645 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('464', 'Wpay', 'Wpay', 'http://www.wpay999.com:80/payment/pay.aspx', '0', '', '', '', 'Wpay', '1', '0', '0', '1', '367', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('464', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('464', '1'), ('464', '3'), ('464', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('464', '1088'), ('464', '1090'), ('464', '1092'), ('464', '1100'), ('464', '1102'), ('464', '1103'), ('464', '1104')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('464', 'number', ''), ('464', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('464', '793487923'), ('464', '793487806')");
        $this->addSql("UPDATE payment_gateway SET code = 'WFu', label = 'WFu' WHERE id = '274'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET code = 'WPay', label = 'WPay' WHERE id = '274'");
        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '464' AND ip IN ('793487923', '793487806')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '464'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '464' AND payment_vendor_id IN ('1088', '1090', '1092', '1100', '1102', '1103', '1104')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '464' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '464' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '464'");
    }
}
