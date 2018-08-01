<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180308141103 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('398', 'BaiHPay', '百海支付', 'http://api.baihpay.com/bank/', '0', '', '', '', 'BaiHPay', '1', '0', '0', '1', '301', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('398', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('398', '1'), ('398', '3'), ('398', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('398', '1'), ('398', '2'), ('398', '3'), ('398', '4'), ('398', '5'), ('398', '6'), ('398', '8'), ('398', '9'), ('398', '10'), ('398', '11'), ('398', '12'), ('398', '13'), ('398', '14'), ('398', '15'), ('398', '16'), ('398', '17'), ('398', '19'), ('398', '217'), ('398', '220'), ('398', '221'), ('398', '223'), ('398', '226'), ('398', '227'), ('398', '228'), ('398', '231'), ('398', '233'), ('398', '234'), ('398', '1090'), ('398', '1092'), ('398', '1097'), ('398', '1103'), ('398', '1104')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('398', 'number', ''), ('398', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('398', '3736854502')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs)
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '398' AND ip = '3736854502'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '398'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '398' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '220', '221', '223', '226', '227', '228', '231', '233', '234', '1090', '1092', '1097', '1103', '1104')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '398' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '398' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '398'");
    }
}
