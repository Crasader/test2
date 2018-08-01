<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180724030720 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('582', 'WanJia', '万家支付', 'http://api.100pay.net:8083/gateway/payapi/1.0/doPay', '0', '', '', '', 'WanJia', '1', '0', '0', '1', '484', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('582', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('582', '1'), ('582', '3')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('582', '1'), ('582', '2'), ('582', '3'), ('582', '4'), ('582', '5'), ('582', '6'), ('582', '8'), ('582', '9'), ('582', '10'), ('582', '11'), ('582', '12'), ('582', '13'), ('582', '14'), ('582', '15'), ('582', '16'), ('582', '17'), ('582', '19'), ('582', '217'), ('582', '234'), ('582', '278'), ('582', '386'), ('582', '393'), ('582', '1088'), ('582', '1098')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('582', 'number', ''), ('582', 'private_key', ''), ('582', 'appId', ''), ('582', 'appSecret', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('582', '2077152956')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '582' AND ip = '2077152956'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '582'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '582' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217' ,'234', '278', '386', '393', '1088', '1098')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '582' AND payment_method_id IN ('1', '3')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '582' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '582'");
    }
}
