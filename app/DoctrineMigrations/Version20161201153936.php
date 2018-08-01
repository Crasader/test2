<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161201153936 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_vendor` (`id`, `payment_method_id`, `name`) VALUES ('1096', '8', '財付通_二維'), ('1097', '3', '微信_手機支付'), ('1098', '3', '支付寶_手機支付'), ('1099', '3', '財付通_手機支付')");
        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (147, 'PuSyun', '普訊網絡', 'http://api.101ka.com/GateWay/Bank/Default.aspx', '0', '', '', '', 'PuSyun', '0', '0', '0', '1', '53', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('147', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('147', '1'), ('147', '3'), ('147', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('147', '1'), ('147', '2'), ('147', '3'), ('147', '4'), ('147', '5'), ('147', '6'), ('147', '8'), ('147', '9'), ('147', '10'), ('147', '11'), ('147', '12'), ('147', '13'), ('147', '14'), ('147', '16'), ('147', '17'), ('147', '1090'), ('147', '1092'), ('147', '1096'), ('147', '1097'), ('147', '1098'), ('147', '1099')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('147', 'number', ''), ('147', 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '147'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '147' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '16', '17', '1090', '1092', '1096', '1097', '1098', '1099')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '147' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '147'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '147'");
        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` IN ('1096', '1097', '1098', '1099')");
    }
}
