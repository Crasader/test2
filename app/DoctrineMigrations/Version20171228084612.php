<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171228084612 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (331, 'HengXinZhiFu', '恒信智付', 'http://open.6xpay.com/online/gateway', '0', '', '', '', 'HengXinZhiFu', '1', '0', '0', '1', '234', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES (331, 156)");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('331', '3'), ('331', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('331', '1'), ('331', '2'), ('331', '3'), ('331', '4'), ('331', '5'), ('331', '6'), ('331', '8'), ('331', '9'), ('331', '10'), ('331', '11'), ('331', '12'), ('331', '13'), ('331', '14'), ('331', '15'), ('331', '16'), ('331', '17'), ('331', '19'), ('331', '217'), ('331', '222'), ('331', '223'), ('331', '226'), ('331', '228'), ('331', '233'), ('331', '234'), ('331', '297'), ('331', '1090'), ('331', '1092'), ('331', '1097'), ('331', '1098'), ('331', '1103'), ('331', '1104')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('331', 'number', ''), ('331', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('331', '795113267')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '331' AND `ip` = '795113267'");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '331'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '331' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '222', '223', '226', '228', '233', '234', '297', '1090', '1092', '1097', '1098', '1103', '1104')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '331' AND `payment_method_id` IN ('3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '331'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '331'");
    }
}
