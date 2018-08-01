<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180409030349 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('427', 'PaySec2', 'Paysec二代', 'https://paysecure.paysec.com/Intrapay/paysec/v1/payIn/sendTokenForm', '0', '', 'payment.https.paysecure.paysec.com', '', 'PaySec2', '1', '0', '0', '1', '330', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('427', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('427', '1'), ('427', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('427', '1'), ('427', '2'), ('427', '3'), ('427', '4'), ('427', '5'), ('427', '6'), ('427', '8'), ('427', '10'), ('427', '11'), ('427', '12'), ('427', '14'), ('427', '15'), ('427', '16'), ('427', '17'), ('427', '278'), ('427', '1103')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('427', 'number', ''), ('427', 'private_key' ,'')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('427', '226377618'), ('427', '886940798')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '427' AND ip IN ('226377618', '886940798')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '427'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '427' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '10', '11', '12', '14', '15', '16', '17', '278', '1103')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '427' AND payment_method_id IN ('1', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '427' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '427'");
    }
}
