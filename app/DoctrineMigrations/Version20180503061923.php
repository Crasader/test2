<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180503061923 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('455', 'Ftp', 'FTP支付', 'https://www.funtopay.com', '0', '', '', '', 'Ftp', '1', '0', '0', '1', '358', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('455', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('455', '1'), ('455', '3')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('455', '1'), ('455', '2'), ('455', '3'), ('455', '4'), ('455', '5'), ('455', '6'), ('455', '8'), ('455', '9'), ('455', '10'), ('455', '11'), ('455', '12'), ('455', '13'), ('455', '14'), ('455', '15'), ('455', '16'), ('455', '17'), ('455', '19'), ('455', '1104')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('455', 'number', ''), ('455', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('455', '1318749402'), ('455', '1310522519'), ('455', '1357506721')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '455' AND ip IN ('1318749402', '1310522519', '1357506721')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '455'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '455' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '1104')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '455' AND payment_method_id IN ('1', '3')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '455' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '455'");
    }
}
