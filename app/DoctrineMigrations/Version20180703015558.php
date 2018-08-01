<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180703015558 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('548', 'HuiYing', '汇赢支付', 'http://cgi.huiwinpay.com/index.php/gateway/requestPay', '0', '', '', '', 'HuiYing', '1', '0', '0', '1', '450', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('548', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('548', '1'), ('548', '3'), ('548', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('548', '1'), ('548', '2'), ('548', '3'), ('548', '4'), ('548', '5'), ('548', '6'), ('548', '8'), ('548', '9'), ('548', '10'), ('548', '11'), ('548', '12'), ('548', '13'), ('548', '14'), ('548', '15'), ('548', '16'), ('548', '17'), ('548', '19'), ('548', '217'), ('548', '223'), ('548', '228'), ('548', '1090'), ('548', '1092'), ('548', '1098'), ('548', '1103'), ('548', '1104'), ('548', '1107'), ('548', '1108')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('548', 'number', ''), ('548', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('548', '736467565')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '548' AND ip = '736467565'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '548'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '548' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '223', '228' ,'1090', '1092', '1098', '1103', '1104', '1107', '1108')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '548' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '548' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '548'");
    }
}
