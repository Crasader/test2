<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171113072925 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (283, 'HengTung', '恒通支付', 'https://gateway.htpays.com/GateWay/Index', '0', '', '', '', 'HengTung', '1', '0', '0', '1', '187', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('283', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('283', '1'), ('283', '3'), ('283', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('283', '1'), ('283', '2'), ('283', '3'), ('283', '4'), ('283', '5'), ('283', '6'), ('283', '8'), ('283', '9'), ('283', '10'), ('283', '11'), ('283', '12'), ('283', '14'), ('283', '16'), ('283', '17'), ('283', '220'), ('283', '223'), ('283', '226'), ('283', '228'), ('283', '1090'), ('283', '1092'), ('283', '1097'), ('283', '1098'), ('283', '1103'), ('283', '1104')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('283', 'number', ''), ('283', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('283', '225513329'), ('283', '225586463')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '283' AND `ip` IN ('225513329', '225586463')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '283'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '283' AND `payment_vendor_id` IN ('1','2','3','4','5','6','8','9','10','11','12','14','16','17','220','223','226','228','1090','1092','1097','1098','1103','1104')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '283' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '283'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '283'");
    }
}
