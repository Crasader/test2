<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180619000000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('512', 'Xhbill', '鑫宝通', 'xhbill.com', '1', '', '', '', 'Xhbill', '1', '0', '0', '1', '414', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('512', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('512', '1'), ('512', '3'), ('512', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('512', '1'), ('512', '2'), ('512', '3'), ('512', '4'), ('512', '5'), ('512', '6'), ('512', '8'), ('512', '9'), ('512', '10'), ('512', '11'), ('512', '12'), ('512', '13'), ('512', '14'), ('512', '15'), ('512', '16'), ('512', '17'), ('512', '19'), ('512', '220'), ('512', '222'), ('512', '278'), ('512', '1088'), ('512', '1090'), ('512', '1092'), ('512', '1102'), ('512', '1103'), ('512', '1107'), ('512', '1111')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('512', 'number', ''), ('512', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('512', '2043237749')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '512' AND ip IN ('2043237749')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '512'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '512' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '220', '222', '278', '1088', '1090', '1092', '1102', '1103', '1107', '1111')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '512' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '512' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '512'");
    }
}
