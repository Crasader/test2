<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160325103507 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM merchant_card_has_payment_vendor WHERE payment_vendor_id = 292');
        $this->addSql('DELETE FROM merchant_level_vendor WHERE payment_vendor_id = 292');
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES (80, 7)");
        $this->addSql('DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = 80 AND payment_method_id = 1');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES (80, 1)");
        $this->addSql('DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = 80 AND payment_method_id = 7');
    }
}
