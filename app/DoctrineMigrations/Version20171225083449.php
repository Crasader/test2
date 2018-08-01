<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171225083449 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (326, 'ShiunFu', '迅付', 'http://api.mashangshouqian.cn', '0', '', 'payment.http.api.mashangshouqian.cn', '', 'ShiunFu', '1', '0', '0', '1', '229', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES (326, 156)");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('326', '3'), ('326', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('326', '1090'), ('326', '1092'), ('326', '1098')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('326', 'number', ''), ('326', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('326', '997109728')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '326' AND `ip` = '997109728'");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '326'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '326' AND `payment_vendor_id` IN ('1090', '1092', '1098')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '326' AND `payment_method_id` IN ('3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '326'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '326'");
    }
}
