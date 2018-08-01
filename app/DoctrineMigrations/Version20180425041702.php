<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180425041702 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('450', 'MushroomPay', '蘑菇支付', 'http://www.rjxyq.cn/payapi/index.php', '0', '', '', '', 'MushroomPay', '1', '0', '0', '1', '353', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('450', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('450', '1'), ('450', '3')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('450', '1'), ('450', '2'), ('450', '3'), ('450', '4'), ('450', '5'), ('450', '6'), ('450', '9'), ('450', '10'), ('450', '11'), ('450', '12'), ('450', '13'), ('450', '14'), ('450', '15'), ('450', '16'), ('450', '17'), ('450', '278'), ('450', '1088')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('450', 'number', ''), ('450', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('450', '401211922')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs)
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '450' AND ip = '401211922'");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '450'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '450' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '9', '10', '11', '12', '13', '14', '15', '16', '17', '278', '1088')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '450' AND payment_method_id IN ('1', '3')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '450' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '450'");
    }
}
