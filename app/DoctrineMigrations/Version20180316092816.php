<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180316092816 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("CREATE TABLE payment_gateway_random_float_vendor (id INT UNSIGNED AUTO_INCREMENT NOT NULL, payment_gateway_id SMALLINT UNSIGNED NOT NULL, payment_vendor_id INT UNSIGNED NOT NULL, PRIMARY KEY(id))");
        $this->addSql("INSERT INTO payment_gateway_random_float_vendor (payment_gateway_id, payment_vendor_id) SELECT pg.id, pghpv.payment_vendor_id FROM payment_gateway pg JOIN payment_gateway_has_payment_vendor pghpv ON (pg.id = pghpv.payment_gateway_id) WHERE pg.random_float = '1' AND pghpv.payment_vendor_id IN (1085, 1090, 1092, 1097, 1098, 1101)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE payment_gateway_random_float_vendor');
    }
}
