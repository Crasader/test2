<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180305072413 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('386', '1'), ('386', '2'), ('386', '3'), ('386', '4'), ('386', '5'), ('386', '6'), ('386', '8'), ('386', '9'), ('386', '11'), ('386', '12'), ('386', '13'), ('386', '14'), ('386', '15'), ('386', '16'), ('386', '17'), ('386', '19')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs)
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '386' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '11', '12', '13', '14', '15', '16', '17', '19')");
    }
}
