<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170510090540 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES (181, 'HaoPay', '好支付', 'http://v2.api.haoteam.net', 0, '', '', '', 'HaoPay', 0, 0, 0, 1, 86, 0)");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES (181, 156)");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES (181, 8)");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES (181, 1090), (181, 1092)");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES (181, 'number', ''), (181, 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = 181");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = 181 AND payment_vendor_id IN (1090, 1092)");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = 181 AND payment_method_id = 8");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE currency = 156 AND payment_gateway_id = 181");
        $this->addSql("DELETE FROM payment_gateway WHERE id = 181");
    }
}
