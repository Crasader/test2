<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180613043044 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('365', '1'), ('365', '2'), ('365', '3'), ('365', '4'), ('365', '5'), ('365', '6'), ('365', '8'), ('365', '9'), ('365', '10'), ('365', '11'), ('365', '12'), ('365', '13'), ('365', '14'), ('365', '15'), ('365', '16'), ('365', '17'), ('365', '223'), ('365', '1090'), ('365', '1092'), ('365', '1097'), ('365', '1098'), ('365', '1103'), ('365', '1104'), ('365', '1107'), ('365', '1111')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs)
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '365' AND payment_vendor_id IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '223', '1090', '1092', '1097', '1098', '1103', '1104', '1107', '1111')");
    }
}
