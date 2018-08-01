<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170418135240 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_has_payment_method WHERE payment_gateway_id = 1 AND payment_method_id = 2");
        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = 1 AND payment_vendor_id IN (1000, 1001, 1002, 1073, 1074, 1075, 1076, 1077, 1078, 1079, 1080, 1081, 1082, 1083, 1084)");
        $this->addSql("DELETE mlm FROM merchant_level_method mlm INNER JOIN merchant m ON mlm.merchant_id = m.id WHERE m.payment_gateway_id = 1 AND mlm.payment_method_id = 2");
        $this->addSql("DELETE mlv FROM merchant_level_vendor mlv INNER JOIN merchant m ON mlv.merchant_id = m.id WHERE m.payment_gateway_id = 1 AND mlv.payment_vendor_id IN (1000, 1001, 1002, 1073, 1074, 1075, 1076, 1077, 1078, 1079, 1080, 1081, 1082, 1083, 1084)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES (1, 1000), (1, 1001), (1, 1002), (1, 1073), (1, 1074), (1, 1075), (1, 1076), (1, 1077), (1, 1078), (1, 1079), (1, 1080), (1, 1081), (1, 1082), (1, 1083), (1, 1084)");
        $this->addSql("INSERT INTO payment_gateway_has_payment_method (payment_gateway_id, payment_method_id) VALUES (1, 2)");
    }
}
