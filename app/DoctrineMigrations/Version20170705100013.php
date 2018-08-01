<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170705100013 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES (192, 'Telepay', '神州付', 'https://payment.skillfully.com.tw/telepay/pay.aspx', 1, 'https://payment.skillfully.com.tw/telepay/checkorder.aspx', 'payment.https.payment.skillfully.com.tw', '', 'Telepay', 0, 0, 0, 1, 97, 0)");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES (192, 156)");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES (192, 1), (192, 3)");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES (192, 279), (192, 1093)");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES (192, 'number', ''), (192, 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = 192");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = 192 AND payment_vendor_id IN (279, 1093)");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = 192 AND payment_method_id IN (1, 3)");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE currency = 156 AND payment_gateway_id = 192");
        $this->addSql("DELETE FROM payment_gateway WHERE id = 192");
    }
}
