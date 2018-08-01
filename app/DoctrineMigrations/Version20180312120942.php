<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180312120942 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('407', 'XinYuZhiFu', '信譽支付', 'https://api.xinyuzhifu.com/sig/v1/union/net', '0', '', 'payment.https.api.xinyuzhifu.com', '', 'XinYuZhiFu', '1', '0', '0', '1', '310', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('407', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('407', '1'), ('407', '3'), ('407', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('407', '1'), ('407', '2'), ('407', '3'), ('407', '4'), ('407', '5'), ('407', '6'), ('407', '11'), ('407', '12'), ('407', '13'), ('407', '14'), ('407', '16'), ('407', '17'), ('407', '1090'), ('407', '1092'), ('407', '1098'), ('407', '1103'), ('407', '1104'), ('407', '1107'), ('407', '1111')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('407', 'number', ''), ('407', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('407', '1731752026')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '407' AND ip = '1731752026'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '407'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '407' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '11', '12', '13', '14', '16', '17', '1090', '1092', '1098', '1103', '1104', '1107', '1111')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '407' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '407' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '407'");
    }
}

