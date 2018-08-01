<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180131035142 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('371', 'PaiPai', '派派支付', 'http://get.tefou.top/api.aspx', '0', '', '', '', 'PaiPai', '1', '0', '0', '1', '274', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('371', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('371', '1'), ('371', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('371', '1'), ('371', '2'), ('371', '3'), ('371', '4'), ('371', '5'), ('371', '6'), ('371', '8'), ('371', '9'), ('371', '10'), ('371', '11'), ('371', '12'), ('371', '13'), ('371', '14'), ('371', '15'), ('371', '16'), ('371', '17'), ('371', '19'), ('371', '217'), ('371', '220'), ('371', '221'), ('371', '223'), ('371', '226'), ('371', '227'), ('371', '228'), ('371', '231'), ('371', '233'), ('371', '234'), ('371', '1090'), ('371', '1092'), ('371', '1103')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('371', 'number', ''), ('371', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('371', '791961385')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '371' AND ip = '791961385'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '371'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '371' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '220', '221', '223', '226', '227', '228', '231', '233', '234', '1090', '1092', '1103')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '371' AND payment_method_id IN ('1', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '371' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '371'");
    }
}
