<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180326032207 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('420', 'YiChiFu', '亿起付', 'yiqpay.com', '1', 'https://query.yiqpay.com/query', 'payment.https.query.yiqpay.com', '', 'YiChiFu', '1', '0', '0', '1', '323', '1')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('420', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('420', '1'), ('420', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('420', '1'), ('420', '2'), ('420', '3'), ('420', '4'), ('420', '5'), ('420', '6'), ('420', '8'), ('420', '9'), ('420', '10'), ('420', '11'), ('420', '12'), ('420', '13'), ('420', '14'), ('420', '15'), ('420', '16'), ('420', '17'), ('420', '19'), ('420', '220'), ('420', '222'), ('420', '1103'), ('420', '1107'), ('420', '1111')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('420', 'number', ''), ('420', 'private_key' ,''), ('420', 'private_key_content', ''), ('420', 'public_key_content', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('420', '3524664194'), ('420', '1902312962'), ('420', '1959528817'), ('420', '236274674'), ('420', '1850745266')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs)
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '420' AND ip IN ('3524664194', '1902312962', '1959528817', '236274674', '1850745266')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '420'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '420' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '220', '222', '1103', '1107', '1111')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '420' AND payment_method_id IN ('1', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '420' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '420'");
    }
}
