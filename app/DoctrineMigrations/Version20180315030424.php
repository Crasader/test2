<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180315030424 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('409', 'ChangHuei', '畅汇', 'https://5u17.cn/controller.action', '0', '', 'payment.https.5u17.cn', '', 'ChangHuei', '1', '0', '0', '1', '312', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('409', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('409', '1'), ('409', '3'), ('409', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('409', '1'), ('409', '2'), ('409', '3'), ('409', '4'), ('409', '5'), ('409', '6'), ('409', '8'), ('409', '9'), ('409', '10'), ('409', '11'), ('409', '12'), ('409', '13'), ('409', '14'), ('409', '15'), ('409', '16'), ('409', '17'), ('409', '19'), ('409', '1103'), ('409', '1104'), ('409', '1107'), ('409', '1111')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('409', 'number', ''), ('409', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('409', '620747892'), ('409', '1741821861'), ('409', '620747902'), ('409', '620747852'), ('409', '1805024434'), ('409', '1805024641'), ('409', '620747884')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '409' AND `ip` IN ('620747892', '1741821861', '620747902', '620747852', '1805024434', '1805024641', '620747884')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '409'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '409' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '1103', '1104', '1107', '1111')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '409' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '409'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '409'");
    }
}
