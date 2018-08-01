<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160516155403 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES (88, 8), (95, 8), (101, 8), (102, 8)");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = 101 AND payment_method_id = 1");
        $this->addSql("INSERT INTO merchant_level_method (merchant_id, level_id, payment_method_id) SELECT merchant_id, level_id, '8' FROM merchant_level_method WHERE merchant_id IN (SELECT merchant_id FROM merchant_level_vendor WHERE payment_vendor_id = 296)");
        $this->addSql("DELETE FROM merchant_level_method WHERE payment_method_id = 1 AND merchant_id IN (SELECT id FROM merchant WHERE payment_gateway_id = 101)");
        $this->addSql("INSERT INTO payment_vendor (id, payment_method_id, name) VALUES (1090, 8, '微信支付__二維')");
        $this->addSql("UPDATE payment_gateway_has_payment_vendor SET payment_vendor_id = 1090 WHERE payment_vendor_id = 296");
        $this->addSql("UPDATE merchant_level_vendor SET payment_vendor_id = 1090 WHERE payment_vendor_id = 296");
        $this->addSql("UPDATE cash_deposit_entry SET payment_method_id = 8, payment_vendor_id = 1090 WHERE payment_vendor_id = 296 AND id >= 201604200000000000");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE cash_deposit_entry SET payment_method_id = 1, payment_vendor_id = 296 WHERE payment_vendor_id = 1090 AND id >= 201604200000000000");
        $this->addSql("UPDATE merchant_level_vendor SET payment_vendor_id = 296 WHERE payment_vendor_id = 1090");
        $this->addSql("UPDATE payment_gateway_has_payment_vendor SET payment_vendor_id = 296 WHERE payment_vendor_id = 1090");
        $this->addSql('DELETE FROM payment_vendor WHERE id = 1090');
        $this->addSql("UPDATE merchant_level_method SET payment_method_id = 1 WHERE merchant_id IN (SELECT id FROM merchant WHERE payment_gateway_id = 101)");
        $this->addSql('DELETE FROM merchant_level_method WHERE payment_method_id = 8');
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES (101, 1)");
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_method_id = 8 AND payment_gateway_id IN (88, 95, 101, 102)");
    }
}
