<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180628120834 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('542', 'YiHuiFu', '亿惠付', 'http://api.y3i2.cn/gateway', '0', '', 'payment.http.api.y3i2.cn', '', 'YiHuiFu', '1', '0', '0', '1', '444', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('542', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('542', '1'), ('542', '3'), ('542', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('542', '1'), ('542', '2'), ('542', '3'), ('542', '4'), ('542', '5'), ('542', '6'), ('542', '8'), ('542', '10'), ('542', '11'), ('542', '12'), ('542', '13'), ('542', '14'), ('542', '15'), ('542', '16'), ('542', '17'), ('542', '19'), ('542', '217'), ('542', '222'), ('542', '223'), ('542', '226'), ('542', '228'), ('542', '234'), ('542', '1092'), ('542', '1098'), ('542', '1103'), ('542', '1104'), ('542', '1108')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('542', 'number', ''), ('542', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('542', '661456362')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '542' AND ip = '661456362'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '542'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '542' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '222', '223', '226', '228', '234', '1092', '1098', '1103', '1104', '1108')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '542' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '542' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '542'");
    }
}
