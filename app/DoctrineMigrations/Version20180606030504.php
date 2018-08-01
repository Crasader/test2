<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180606030504 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('498', 'SuLong', '速龙支付', 'https://pay.islpay.com/gateway?input_charset=UTF-8', '0', '', 'payment.https.api.islpay.com', '', 'SuLong', '1', '0', '0', '1', '401', '1')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('498', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('498', '1'), ('498', '3'), ('498', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('498', '1'), ('498', '2'), ('498', '3'), ('498', '4'), ('498', '5'), ('498', '6'), ('498', '8'), ('498', '9'), ('498', '10'), ('498', '11'), ('498', '12'), ('498', '13'), ('498', '14'), ('498', '15'), ('498', '16'), ('498', '17'), ('498', '19'), ('498', '220'), ('498', '222'), ('498', '1100'), ('498', '1102'), ('498', '1103'), ('498', '1104'), ('498', '1111')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('498', 'number', ''), ('498', 'private_key', ''), ('498', 'private_key_content', ''), ('498', 'public_key_content', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('498', '3524664194'), ('498', '1902312962'), ('498', '236274674'), ('498', '1959528817'), ('498', '1032711132'), ('498', '1902300854'), ('498', '3524664212')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '498' AND ip IN ('3524664194', '1902312962', '236274674', '1959528817', '1032711132', '1902300854', '3524664212')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '498'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '498' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '220', '222', '1100', '1102', '1103', '1104', '1111')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '498' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '498' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '498'");
    }
}
