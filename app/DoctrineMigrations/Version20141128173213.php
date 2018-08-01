<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141128173213 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT payment_gateway_has_payment_vendor(payment_gateway_id, payment_vendor_id) VALUES (6, 6), (6, 9), (6, 10), (6, 11), (6, 13), (6, 14), (6, 15), (6, 16), (6, 19), (6, 220), (6, 222), (6, 226), (6, 228), (6, 234)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = 6 AND payment_vendor_id IN (6, 9, 10, 11, 13, 14, 15, 16, 19, 220, 222, 226, 228, 234)");
    }
}
