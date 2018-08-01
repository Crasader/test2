<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180709021652 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, label, bind_ip, removed, withdraw, hot, order_id, upload_key) VALUES ('560', 'MyPay', 'my pay', '', '0', '', '', '', 'MyPay', '1', '0', '0', '1', '462', '0')");
        $this->addSql("INSERT INTO payment_gateway_currency (payment_gateway_id, currency) VALUES ('560', '156')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES ('560', '1'), ('560', '3'), ('560', '8')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('560', '278'), ('560', '1088'), ('560', '1090'), ('560', '1092'), ('560', '1097'), ('560', '1098'), ('560', '1102'), ('560', '1103'), ('560', '1104'), ('560', '1107'), ('560', '1108'), ('560', '1111')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('560', 'number', ''), ('560', 'private_key', ''), ('560', 'orgId', ''), ('560', 'postUrl', '')");
        $this->addSql("INSERT INTO payment_gateway_bind_ip (payment_gateway_id, ip) VALUES ('560', '1757936696'), ('560', '1757918668'), ('560', '600423297'), ('560', '602253322'), ('560', '1757934097'), ('560', '602273215'), ('560', '599498368')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_bind_ip WHERE payment_gateway_id = '560' AND ip IN ('1757936696', '1757918668', '600423297', '602253322', '1757934097', '602273215', '599498368')");
        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '560'");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '560' AND payment_vendor_id IN ('278', '1088', '1090', '1092', '1097', '1098', '1102', '1103', '1104', '1107', '1108', '1111')");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = '560' AND payment_method_id IN ('1', '3', '8')");
        $this->addSql("DELETE FROM payment_gateway_currency WHERE payment_gateway_id = '560' AND currency = '156'");
        $this->addSql("DELETE FROM payment_gateway WHERE id = '560'");
    }
}
