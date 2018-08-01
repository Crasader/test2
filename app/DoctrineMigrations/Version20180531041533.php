<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180531041533 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO payment_gateway_has_payment_vendor (payment_gateway_id, payment_vendor_id) VALUES ('319', '1115'), ('319', '1118')");
        $this->addSql("DELETE mlv FROM `merchant_level_vendor` mlv INNER JOIN `merchant` m ON m.`id` = mlv.`merchant_id` WHERE m.`payment_gateway_id` = '319' AND mlv.`payment_vendor_id` IN ('1090', '1097')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '319' AND `payment_vendor_id` IN ('1090', '1097')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_has_payment_vendor WHERE payment_gateway_id = '319' AND payment_vendor_id IN ('1115', '1118')");
    }
}
