<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180131033713 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('288', '8'), ('288', '10'), ('288', '15'), ('288', '19'), ('288', '217'), ('288', '220'), ('288', '221'), ('288', '222'), ('288', '223'), ('288', '226'), ('288', '228'), ('288', '229'), ('288', '234'), ('288', '278'), ('288', '309'), ('288', '312'), ('288', '315')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '288' AND `payment_vendor_id` IN ('8', '10', '15', '19', '217', '220', '221', '222', '223', '226', '228', '229', '234', '278', '309', '312', '315')");
    }
}
