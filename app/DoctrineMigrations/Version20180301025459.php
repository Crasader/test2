<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180301025459 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('392', 'YiBaoTong', '億寶通', 'https://pay.yibaotown.com/gateway?input_charset=UTF-8', '1', '', 'payment.https.query.yibaotown.com', '', 'YiBaoTong', '1', '0', '0', '1', '295', '1')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('392', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('392', '1')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('392', '8'), ('392', '9'), ('392', '10'), ('392', '11'), ('392', '12'), ('392', '14'), ('392', '1102')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('392', 'number', ''), ('392', 'private_key' ,''), ('392', 'private_key_content', ''), ('392', 'public_key_content', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('392', '3524664194'), ('392', '1902312962'), ('392', '236274674'), ('392', '1959528817')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs)
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '392' AND ip IN ('3524664194', '1902312962', '236274674', '1959528817')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '392'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '392' AND payment_vendor_id IN ('8', '9', '10', '11', '12', '14', '1102')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '392' AND payment_method_id = '1'");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '392' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '392'");
    }
}
