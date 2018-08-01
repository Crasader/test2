<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170109130642 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (159, 'XunBao', '訊寶商務', 'http://gateway.xunbaopay9.com/chargebank.aspx', '1', 'http://gateway.xunbaopay9.com/Search.aspx', 'payment.http.gateway.xunbaopay9.com', '', 'XunBao', '0', '0', '0', '1', '64', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES (159, 156)");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('159', '1'), ('159', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('159', '1'), ('159', '2'), ('159', '3'), ('159', '4'), ('159', '5'), ('159', '6'), ('159', '7'), ('159', '8'), ('159', '9'), ('159', '10'), ('159', '11'), ('159', '12'), ('159', '13'), ('159', '14'), ('159', '15'), ('159', '16'), ('159', '17'), ('159', '19'), ('159', '217'), ('159', '220'), ('159', '221'), ('159', '223'), ('159', '226'), ('159', '227'), ('159', '228'), ('159', '231'), ('159', '233'), ('159', '234'), ('159', '297'), ('159', '1090'), ('159', '1092')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('159', 'number', ''), ('159', 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '159'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '159' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '220', '221', '223', '226', '227', '228', '231', '233', '234', '297', '1090', '1092')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '159' AND `payment_method_id` IN ('1', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '159'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '159'");
    }
}
