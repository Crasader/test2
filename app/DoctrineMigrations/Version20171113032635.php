<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171113032635 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (281, 'XingHang', '星航支付', 'http://soso.xinghjk.com/online/gateway', '0', '', 'payment.http.soso.xinghjk.com', '', 'XingHang', '1', '0', '0', '1', '185', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('281', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('281', '1'), ('281', '3'), ('281', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('281', '1'), ('281', '2'), ('281', '3'), ('281', '4'), ('281', '5'), ('281', '6'), ('281', '8'), ('281', '9'), ('281', '10'), ('281', '11'), ('281', '12'), ('281', '13'), ('281', '14'), ('281', '15'), ('281', '16'), ('281', '17'), ('281', '19'), ('281', '217'), ('281', '222'), ('281', '223'), ('281', '226'), ('281', '228'), ('281', '233'), ('281', '234'), ('281', '297'), ('281', '1090'), ('281', '1092'), ('281', '1097'), ('281', '1098'), ('281', '1099'), ('281', '1103'), ('281', '1104'), ('281', '1107'), ('281', '1108')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('281', 'number', ''), ('281', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('281', '1734180787')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '281' AND `ip` = '1734180787'");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '281'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '281' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '222', '223', '226', '228', '233', '234', '297', '1090', '1092', '1097', '1098', '1099', '1103', '1104', '1107', '1108')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '281' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '281'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '281'");
    }
}
