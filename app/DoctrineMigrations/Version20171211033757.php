<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171211033757 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (316, 'YiHeXin', '益和信', 'http://api.jlfwx.com/pay.aspx', '0', '', '', '', 'YiHeXin', '0', '0', '0', '1', '219', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('316', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('316', '1'), ('316', '3'), ('316', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('316', '1'), ('316', '2'), ('316', '3'), ('316', '4'), ('316', '5'), ('316', '6'), ('316', '8'), ('316', '9'), ('316', '10'), ('316', '11'), ('316', '12'), ('316', '13'), ('316', '14'), ('316', '15'), ('316', '16'), ('316', '17'), ('316', '19'), ('316', '1090'), ('316', '1092'), ('316', '1097'), ('316', '1098'), ('316', '1103'), ('316', '1104'), ('316', '1107'), ('316', '1108'), ('316', '1111')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('316', 'number', ''), ('316', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('316', '3549801311')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '306' AND `ip` = '3549801311'");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '316'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '316' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '1090', '1092', '1097', '1098', '1103', '1104', '1107', '1108', '1111')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '316' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '316'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '316'");
    }
}
