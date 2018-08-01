<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180730070202 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('596', 'JiouYi', '久易', 'https://vip.dddyn.com/pay/gateway', '0', '', '', '', 'JiouYi', '1', '0', '0', '1', '498', '1')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('596', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('596', '1'), ('596', '3'), ('596', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('596', '1'), ('596', '2'), ('596', '3'), ('596', '4'), ('596', '5'), ('596', '6'), ('596', '8'), ('596', '10'), ('596', '11'), ('596', '12'), ('596', '13'), ('596', '14'), ('596', '16'), ('596', '17'), ('596', '219'), ('596', '220'), ('596', '222'), ('596', '226'), ('596', '1092'), ('596', '1098'), ('596', '1111')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('596', 'number', ''), ('596', 'private_key', ''), ('596', 'private_key_content', ''), ('596', 'public_key_content', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('596', '1779306247'), ('596', '795134544'), ('596', '2346715773')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '596' AND ip IN ('1779306247', '795134544', '2346715773')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '596'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '596' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '10', '11', '12', '13', '14', '16', '17', '219', '220', '222', '226', '1092', '1098', '1111')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '596' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '596' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '596'");
    }
}
