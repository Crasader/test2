<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160112101850 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_vendor` (`id`, `payment_method_id`, `name`) VALUES ('1086', '2', '天下一卡通专项'), ('1087', '2', '盛付通一卡通')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id` ,`payment_method_id`) VALUES ('64', '2')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id` ,`payment_vendor_id`) VALUES ('64', '1000'), ('64', '1001'), ('64', '1002'), ('64', '1073'), ('64', '1074'), ('64', '1075'), ('64', '1076'), ('64', '1077'), ('64', '1078'), ('64', '1079'), ('64', '1080'), ('64', '1081'), ('64', '1082'), ('64', '1083'), ('64', '1086'), ('64', '1087')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE payment_gateway_id = '64' AND payment_vendor_id IN ('1000', '1001', '1002', '1073', '1074', '1075', '1076', '1077', '1078', '1079', '1080', '1081', '1082', '1083', '1086', '1087')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE payment_gateway_id = '64' AND payment_method_id = '2'");
        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` IN ('1086', '1087')");
    }
}
