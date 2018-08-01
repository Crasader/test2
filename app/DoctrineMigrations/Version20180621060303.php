<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180621060303 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('453', 'OnlineBankSettleCycle', ''), ('453', 'QuickPaySettleCycle', ''), ('453', 'QrcodeSettleCycle', ''), ('453', 'PhonePaySettleCycle', '')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('453', '2'), ('453', '5'), ('453', '8'), ('453', '9'), ('453', '10'), ('453', '12'), ('453', '13'), ('453', '14'), ('453', '15'), ('453', '278'), ('453', '1088'), ('453', '1092')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '453' AND `payment_vendor_id` IN ('2', '5', '8', '9', '10', '12', '13', '14', '15', '278', '1088', '1092')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '453' AND `name` IN ('OnlineBankSettleCycle', 'QuickPaySettleCycle', 'QrcodeSettleCycle' ,'PhonePaySettleCycle')");
    }
}
