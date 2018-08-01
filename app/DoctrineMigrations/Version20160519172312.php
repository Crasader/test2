<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160519172312 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES (104, 8)");
        $this->addSql("INSERT INTO merchant_level_method (merchant_id, level_id, payment_method_id) SELECT merchant_id, level_id, '8' FROM merchant_level_method WHERE merchant_id IN (SELECT merchant_id FROM merchant_level_vendor WHERE payment_vendor_id = 314)");
        $this->addSql("INSERT INTO payment_vendor (id, payment_method_id, name) VALUES (1092, 8, '支付宝__二維')");
        $this->addSql("UPDATE payment_gateway_has_payment_vendor SET payment_vendor_id = 1092 WHERE payment_vendor_id = 314");
        $this->addSql("UPDATE merchant_level_vendor SET payment_vendor_id = 1092 WHERE payment_vendor_id = 314");
        $this->addSql("UPDATE cash_deposit_entry SET payment_method_id = 8, payment_vendor_id = 1092 WHERE payment_vendor_id = 314 AND id >= 201605200000000000");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE cash_deposit_entry SET payment_method_id = 1, payment_vendor_id = 314 WHERE payment_vendor_id = 1092 AND id >= 201605200000000000");
        $this->addSql("UPDATE merchant_level_vendor SET payment_vendor_id = 314 WHERE payment_vendor_id = 1092");
        $this->addSql("UPDATE payment_gateway_has_payment_vendor SET payment_vendor_id = 314 WHERE payment_vendor_id = 1092");
        $this->addSql('DELETE FROM payment_vendor WHERE id = 1092');
        $this->addSql('DELETE FROM merchant_level_method WHERE payment_method_id = 8 AND merchant_id IN (SELECT merchant_id FROM merchant_level_vendor WHERE payment_vendor_id = 314)');
        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_method_id = 8 AND payment_gateway_id IN (104)");
    }
}
