<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180207023409 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('379', 'IShangRsa', '艾尚RSA', 'http://api.jghye.top/trade/handle', '0', '', 'payment.http.api.jghye.top', '', 'IShangRsa', '0', '0', '0', '1', '282', '1')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('379', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('379', '1'), ('379', '3'), ('379', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('379', '1'), ('379', '2'), ('379', '3'), ('379', '4'), ('379', '6'), ('379', '8'), ('379', '9'), ('379', '10'), ('379', '11'), ('379', '12'), ('379', '13'), ('379', '14'), ('379', '15'), ('379', '16'), ('379', '17'), ('379', '19'), ('379', '234'), ('379', '1090'), ('379', '1092'), ('379', '1097'), ('379', '1098'), ('379', '1103'), ('379', '1104'), ('379', '1111')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('379', 'number', ''), ('379', 'private_key' ,''), ('379', 'private_key_content', ''), ('379', 'public_key_content', ''), ('379', 'platformNo', ''), ('379', 'tradeRate', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('379', '791986510')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '379' AND ip = '791986510'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '379'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '379' AND payment_vendor_id IN ('1', '2', '3', '4', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '234', '1090', '1092', '1097', '1098', '1103', '1104', '1111')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '379' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '379' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '379'");
    }
}
