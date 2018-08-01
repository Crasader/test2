<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171227052352 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('271', '1097'), ('271', '1107')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('271', '3')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '271' AND `payment_method_id` = '3'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '271' AND `payment_vendor_id` IN ('1097', '1107')");
    }
}
