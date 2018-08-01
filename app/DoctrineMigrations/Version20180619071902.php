<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180619071902 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('509', 'YunJian', '云尖支付', 'http://pay.yunjiansc.cn/apisubmit', '0', '', '', '', 'YunJian', '0', '0', '0', '1', '411', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('509', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('509', '1'), ('509', '3'), ('509', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('509', '1'), ('509', '2'), ('509', '3'), ('509', '4'), ('509', '5'), ('509', '6'), ('509', '8'), ('509', '9'), ('509', '10'), ('509', '11'), ('509', '12'), ('509', '13'), ('509', '14'), ('509', '15'), ('509', '16'), ('509', '17'), ('509', '19'), ('509', '228'), ('509', '1090'), ('509', '1092'), ('509', '1097'), ('509', '1098'), ('509', '1103'), ('509', '1104'), ('509', '1107'), ('509', '1108'), ('509', '1111')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('509', 'number', ''), ('509', 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '509'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '509' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '228' ,'1090', '1092', '1097', '1098', '1103', '1104', '1107', '1108', '1111')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '509' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '509' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '509'");
    }
}
