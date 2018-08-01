<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180626042946 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('533', 'ZhiFuJia', '支付家', 'http://138pay.cc/Pay_Index.html', '0', '', '', '', 'ZhiFuJia', '1', '0', '0', '1', '435', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('533', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('533', '3'), ('533', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('533', '1098'), ('533', '1103'), ('533', '1104')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('533', 'number', ''), ('533', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('533', '793443770')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '533' AND `ip` = '793443770'");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '533'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '533' AND `payment_vendor_id` IN ('1098', '1103', '1104')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '533' AND `payment_method_id` IN ('3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `payment_gateway_id` = '533' AND currency = '156'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE id = '533'");
    }
}