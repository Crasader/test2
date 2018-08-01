<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180523054614 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('481', 'YunTongZhiFu', '云通支付', 'http://api.csfpay.com.cn/bank/', '0', '', '', '', 'YunTongZhiFu', '0', '0', '0', '1', '384', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('481', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('481', '1'), ('481', '3'), ('481', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('481', '1'), ('481', '2'), ('481', '3'), ('481', '4'), ('481', '5'), ('481', '6'), ('481', '8'), ('481', '9'), ('481', '10'), ('481', '11'), ('481', '12'), ('481', '13'), ('481', '14'), ('481', '15'), ('481', '16'), ('481', '17'), ('481', '19'), ('481', '217'), ('481', '220'), ('481', '221'), ('481', '223'), ('481', '226'), ('481', '227'), ('481', '228'), ('481', '231'), ('481', '233'), ('481', '234'), ('481', '1092'), ('481', '1097'), ('481', '1098'), ('481', '1104')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('481', 'number', ''), ('481', 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '481'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '481' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '220', '221', '223', '226', '227', '228', '231', '233', '234', '1092', '1097', '1098', '1104')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '481' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '481' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '481'");
    }
}
