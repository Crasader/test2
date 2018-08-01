<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170614160510 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '178' AND `payment_vendor_id` = '1102'");
        $this->addSql("DELETE mlv FROM merchant_level_vendor mlv INNER JOIN merchant m ON mlv.merchant_id = m.id WHERE m.payment_gateway_id = '178' AND mlv.payment_vendor_id = '1102'");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('178', '1'), ('178', '2'), ('178', '3'), ('178', '4'), ('178', '5'), ('178', '6'), ('178', '8'), ('178', '9'), ('178', '10'), ('178', '11'), ('178', '14'), ('178', '15'), ('178', '16'), ('178', '17'), ('178', '19'), ('178', '278')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '178' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '14', '15', '16', '17', '19', '278')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('178', '1102')");
    }
}
