<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180630171716 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('559', 'FeiMiaoPay', '飞秒支付', 'https://pay.zdfmf.com/gateway?input_charset=UTF-8', '0', '', 'payment.https.api.zdfmf.com', '', 'FeiMiaoPay', '1', '0', '0', '1', '461', '1')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('559', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('559', '1'), ('559', '3'), ('559', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('559', '1'), ('559', '2'), ('559', '3'), ('559', '4'), ('559', '5'), ('559', '6'), ('559', '8'), ('559', '9'), ('559', '10'), ('559', '11'), ('559', '12'), ('559', '13'), ('559', '14'), ('559', '15'), ('559', '16'), ('559', '17'), ('559', '19'), ('559', '220'), ('559', '222'), ('559', '1092'), ('559', '1098'), ('559', '1111')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('559', 'number', ''), ('559', 'private_key', ''), ('559', 'private_key_content', ''), ('559', 'public_key_content', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('559', '2043237737'), ('559', '1032711140')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '559' AND ip IN ('2043237737', '1032711140')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '559'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '559' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '220', '222', '1092', '1098', '1111')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '559' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '559' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '559'");
    }
}
