<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150515094222 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO `payment_vendor` (`id`, `payment_method_id`, `name`) VALUES ('1073', '2', '骏网一卡通'), ('1074', '2', '盛大卡'), ('1075', '2', '征途卡'), ('1076', '2', 'Q币卡'), ('1077', '2', '久游卡'), ('1078', '2', '网易卡'), ('1079', '2', '完美卡'), ('1080', '2', '搜狐卡'), ('1081', '2', '纵游一卡通'), ('1082', '2', '天下一卡通'), ('1083', '2', '天宏一卡通')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id` ,`payment_method_id`) VALUES ('1', '2')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id` ,`payment_vendor_id`) VALUES ('1', '1000'), ('1', '1001'), ('1', '1002'), ('1', '1073'), ('1', '1074'), ('1', '1075'), ('1', '1076'), ('1', '1077'), ('1', '1078'), ('1', '1079'), ('1', '1080'), ('1', '1081'), ('1', '1082'), ('1', '1083')");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE payment_gateway_id = '1' AND payment_vendor_id IN ('1000', '1001', '1002', '1073', '1074', '1075', '1076', '1077', '1078', '1079', '1080', '1081', '1082', '1083')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE payment_gateway_id = '1' AND payment_method_id = '2'");
        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` IN ('1073', '1074', '1075', '1076', '1077', '1078', '1079', '1080', '1081', '1082', '1083')");
    }
}
