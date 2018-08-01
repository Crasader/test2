<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180607123407 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('500', 'MingJieFu', '明捷付', 'mjzfpay.com', '0', '', '', '', 'MingJieFu', '1', '0', '0', '1', '403', '1')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('500', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('500', '3'), ('500', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('500', '1092'), ('500', '1098'), ('500', '1103'), ('500', '1104'), ('500', '1107'), ('500', '1108'), ('500', '1111'), ('500', '1115'), ('500', '1118')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('500', 'number', ''), ('500', 'private_key', ''), ('500', 'private_key_content', ''), ('500', 'public_key_content', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('500', '794553307'), ('500', '793482791'), ('500', '793482643'), ('500', '793488299')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '500' AND ip IN ('794553307', '793482791', '793482643', '793488299')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '500'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '500' AND payment_vendor_id IN ('1092', '1098', '1103', '1104', '1107', '1108', '1111', '1115', '1118')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '500' AND payment_method_id IN ('3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '500' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '500'");
    }
}
