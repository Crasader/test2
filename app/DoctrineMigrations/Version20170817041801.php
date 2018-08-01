<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170817041801 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('207', 'ShunFoo', '順付', 'http://interface.shunfoo.com/Bank/index.aspx', '1', 'http://interface.shunfoo.com/search.aspx', 'payment.http.interface.shunfoo.com', '', 'ShunFoo', '0', '0', '0', '1', '111', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('207', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('207', '1'), ('207', '3'), ('207', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('207', '1'), ('207', '2'), ('207', '3'), ('207', '4'), ('207', '5'), ('207', '6'), ('207', '8'), ('207', '9'), ('207', '10'), ('207', '11'), ('207', '12'), ('207', '13'), ('207', '14'), ('207', '15'), ('207', '16'), ('207', '17'), ('207', '19'), ('207', '217'), ('207', '220'), ('207', '221'), ('207', '223'), ('207', '226'), ('207', '227'), ('207', '228'), ('207', '231'), ('207', '233'), ('207', '234'), ('207', '297'), ('207', '1090'), ('207', '1092'), ('207', '1097'), ('207', '1098'), ('207', '1103')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('207', 'number', ''), ('207', 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '207'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '207' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '220', '221', '223', '226', '227', '228', '231', '233', '234', '297', '1090', '1092', '1097', '1098', '1103')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '207' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '207'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '207'");
    }
}
