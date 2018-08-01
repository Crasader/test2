<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180724085851 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('584', 'AiNung', '爱农支付', 'http://gpay.chinagpay.com/bas/FrontTrans', '0', '', '', '', 'AiNung', '1', '0', '0', '1', '486', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('584', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('584', '1'), ('584', '3')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('584', '1'), ('584', '2'), ('584', '3'), ('584', '4'), ('584', '6'), ('584', '9'), ('584', '12'), ('584', '14'), ('584', '16'), ('584', '17'), ('584', '19'), ('584', '1088'), ('584', '1102')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('584', 'number', ''), ('584', 'private_key', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('584', '3391852772'), ('584', '3391852770'), ('584', '2002398106'), ('584', '2002398099')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '584' AND ip IN ('3391852772', '3391852770', '2002398106', '2002398099')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '584'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '584' AND payment_vendor_id IN ('1', '2', '3', '4', '6', '9', '12', '14', '16', '17', '19', '1088', '1102')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '584' AND payment_method_id IN ('1', '3')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '584' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '584'");
    }
}
