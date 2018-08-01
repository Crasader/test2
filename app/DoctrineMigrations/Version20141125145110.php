<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141125145110 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `bind_ip`, `support`, `label`) VALUES ('86', 'BaoFoo88', '第四方寶付', 'http://api.anlafu.com/', '0', '', '', '', '0', '1', 'BaoFoo88')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ( '86', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('86', '1')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('86', '1'), ('86', '2'), ('86', '3'), ('86', '4'), ('86', '5'), ('86', '6'), ('86', '8'), ('86', '9'), ('86', '10'), ('86', '12'), ('86', '14'), ('86', '15'), ('86', '16'), ('86', '17'), ('86', '19'), ('86', '217'), ('86', '220'), ('86', '221'), ('86', '222'), ('86', '223'), ('86', '234') ");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '86' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '12', '14', '15', '16', '17', '19', '217', '220', '221', '222', '223', '234')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '86' AND `payment_method_id` = '1'");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '86'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '86'");
    }
}
