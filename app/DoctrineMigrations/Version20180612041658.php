<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180612041658 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('239', '1'), ('239', '2'), ('239', '3'), ('239', '4'), ('239', '5'), ('239', '6'), ('239', '9'), ('239', '11'), ('239', '12'), ('239', '13'), ('239', '14'), ('239', '16'), ('239', '17'), ('239', '278'), ('239', '1100'), ('239', '1111'), ('239', '1115'), ('239', '1118')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '239' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '9', '11', '12', '13', '14', '16', '17', '278', '1100', '1111', '1115', '1118')");
    }
}
