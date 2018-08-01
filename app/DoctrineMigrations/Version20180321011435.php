<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180321011435 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('414', 'YiFuPay', '易付支付', 'http://gateway.rong-he.net/online/gateway', '0', '', '', '', 'YiFuPay', '1', '0', '0', '1', '317', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('414', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('414', '1'), ('414', '3'), ('414', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('414', '1'), ('414', '2'), ('414', '3'), ('414', '4'), ('414', '5'), ('414', '6'), ('414', '7'), ('414', '8'), ('414', '9'), ('414', '10'), ('414', '11'), ('414', '12'), ('414', '13'), ('414', '14'), ('414', '15'), ('414', '16'), ('414', '17'), ('414', '19'), ('414', '217'), ('414', '222'), ('414', '223'), ('414', '226'), ('414', '228'), ('414', '233'), ('414', '234'), ('414', '1092'), ('414', '1098')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('414', 'number', ''), ('414', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('414', '1734895653')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs)
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '414' AND ip = '1734895653'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '414'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '414' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '222', '223', '226', '228', '233', '234', '1092', '1098')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '414' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '414' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '414'");
    }
}
