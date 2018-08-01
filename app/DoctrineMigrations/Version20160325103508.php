<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160325103508 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_vendor (id, payment_method_id, name) VALUES (1089, 7, 'Neteller')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES (80, 1089)");
        $this->addSql('DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = 80 AND payment_vendor_id = 292');
        $this->addSql('DELETE FROM payment_vendor WHERE id = 292');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_vendor (id, payment_method_id, name) VALUES (292, 1, 'Neteller')");
        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES (80, 292)");
        $this->addSql('DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = 80 AND payment_vendor_id = 1089');
        $this->addSql('DELETE FROM payment_vendor WHERE id = 1089');
    }
}
