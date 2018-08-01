<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180719040152 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('574', 'TongTianBao', '通天宝', 'ttbpay.net', '0', '', '', '', 'TongTianBao', '1', '0', '0', '1', '476', '1')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('574', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('574', '1'), ('574', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('574', '1'), ('574', '2'), ('574', '3'), ('574', '4'), ('574', '5'), ('574', '6'), ('574', '8'), ('574', '9'), ('574', '10'), ('574', '11'), ('574', '12'), ('574', '13'), ('574', '14'), ('574', '15'), ('574', '16'), ('574', '17'), ('574', '19'), ('574', '220'), ('574', '221'), ('574', '222'), ('574', '226'), ('574', '234'), ('574', '308'), ('574', '309'), ('574', '311'), ('574', '321'), ('574', '340'), ('574', '1103'), ('574', '1111')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('574', 'number', ''), ('574', 'private_key', ''), ('574', 'private_key_content', ''), ('574', 'public_key_content', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('574', '226470258'), ('574', '226378720')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '574' AND ip IN ('226470258', '226378720')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '574'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '574' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '220', '221', '222', '226', '234', '308', '309', '311', '321', '340', '1103', '1111')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '574' AND payment_method_id IN ('1', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '574' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '574'");
    }
}
