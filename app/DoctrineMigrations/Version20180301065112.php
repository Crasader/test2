<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180301065112 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('393', 'UNPay', 'UNPAY樂天付', 'http://center.qpay888.com/Bank/', '0', '', '', '', 'UNPay', 1, 0, 0, 1, '296', 0)");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('393', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('393', '1'), ('393', '3'), ('393', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('393', '1'), ('393', '3'), ('393', '4'), ('393', '5'), ('393', '6'), ('393', '9'), ('393', '11'), ('393', '12'), ('393', '16'), ('393', '17'), ('393', '19'), ('393', '1088'), ('393', '1090'), ('393', '1098'), ('393', '1103'), ('393', '1111')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('393', 'number', ''), ('393', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('393', '1958845034'), ('393', '1958844544'), ('393', '3736855363'), ('393', '469720287'), ('393', '469718147')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '393' AND `ip` IN ('1958845034', '1958844544', '3736855363', '469720287', '469718147')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '393'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '393' AND `payment_vendor_id` IN ('1', '3', '4', '5', '6', '9', '11', '12', '16', '17', '19', '1088', '1090', '1098', '1103', '1111')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '393' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '393'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '393'");
    }
}
