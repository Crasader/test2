<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180730030919 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('595', 'SuiFu', '随付', 'suifupay.com', '0', '', '', '', 'SuiFu', '1', '0', '0', '1', '497', '1')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('595', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('595', '1'), ('595', '3'), ('595', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('595', '1'), ('595', '2'), ('595', '3'), ('595', '4'), ('595', '5'), ('595', '6'), ('595', '8'), ('595', '9'), ('595', '10'), ('595', '11'), ('595', '12'), ('595', '13'), ('595', '14'), ('595', '15'), ('595', '16'), ('595', '17'), ('595', '19'), ('595', '220'), ('595', '222'), ('595', '278'), ('595', '1088'), ('595', '1090'), ('595', '1092'), ('595', '1098'), ('595', '1102'), ('595', '1103')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('595', 'number', ''), ('595', 'private_key', ''), ('595', 'private_key_content', ''), ('595', 'public_key_content', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('595', '2043237721'), ('595', '1902312968'), ('595', '236274680')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '595' AND ip IN ('2043237721', '1902312968', '236274680')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '595'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '595' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '220', '222', '278', '1088', '1090', '1092', '1098', '1102', '1103')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '595' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '595' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '595'");
    }
}
