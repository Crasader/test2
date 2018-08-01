<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180329192738 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('423', 'KuaiRuBao', '快入宝', 'https://pay.krbapi.com/b', '0', '', '', '', 'KuaiRuBao', '1', '0', '0', '1', '326', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('423', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('423', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('423', '1090'), ('423', '1092')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('423', 'number', ''), ('423', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('423', '1998372264'), ('423', '1998369890'), ('423', '1998369386'), ('423', '1998378403')");
        $this->addSql("INSERT INTO payment_gateway_random_float_vendor (payment_gateway_id, payment_vendor_id) VALUES ('423', '1090'), ('423', '1092')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs)
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_random_float_vendor WHERE payment_gateway_id = '423' AND payment_vendor_id IN ('1090', '1092')");
        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '423' AND ip IN ('1998372264', '1998369890', '1998369386', '1998378403')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '423'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '423' AND payment_vendor_id IN ('1090', '1092')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '423' AND payment_method_id = '8'");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '423' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '423'");
    }
}
