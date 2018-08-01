<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180202042932 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('363', 'GaoHuiTong', '高匯通', 'http://service.gaohuitong.com/PayApi/bankPay', '0', '', 'payment.http.service.gaohuitong.com', '', 'GaoHuiTong', '1', '0', '0', '1', '266', '1')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('363', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('363', '1'), ('363', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('363', '1'), ('363', '2'), ('363', '3'), ('363', '4'), ('363', '5'), ('363', '6'), ('363', '7'), ('363', '8'), ('363', '9'), ('363', '10'), ('363', '11'), ('363', '12'), ('363', '13'), ('363', '14'), ('363', '15'), ('363', '16'), ('363', '17'), ('363', '19'), ('363', '217'), ('363', '219'), ('363', '220'), ('363', '221'), ('363', '222'), ('363', '223'), ('363', '226'), ('363', '228'), ('363', '234'), ('363', '278'), ('363', '307'), ('363', '308'), ('363', '309'), ('363', '1111')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('363', 'number', ''), ('363', 'private_key' ,''), ('363', 'private_key_content', ''), ('363', 'public_key_content', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('363', '3528065602')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs)
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '363' AND ip = '3528065602'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '363'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '363' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '219', '220', '221', '222', '223', '226', '228', '234', '278', '307', '308', '309', '1111')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '363' AND payment_method_id IN ('1', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '363' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '363'");
    }
}
