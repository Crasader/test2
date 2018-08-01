<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180614114118 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('506', 'XinHuiHe', '新汇合支付', 'https://gateway.huihezhifu.com', '0', '', '', '', 'XinHuiHe', '1', '0', '0', '1', '408', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('506', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('506', '1'), ('506', '3'), ('506', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('506', '1092'), ('506', '1098'), ('506', '1100'), ('506', '1102'), ('506', '1111')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('506', 'number', ''), ('506', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('506', '792002096')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '506' AND ip = '792002096'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '506'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '506' AND payment_vendor_id IN ('1092', '1098', '1100', '1102', '1111')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '506' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '506' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '506'");
    }
}