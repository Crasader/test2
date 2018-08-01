<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180322130506 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('418', 'APay', 'A付', 'https://gateway.rffbe.top', '0', '', 'payment.https.gateway.aabill.com', '', 'APay', '1', '0', '0', '1', '321', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('418', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('418', '1'), ('418', '3'), ('418', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('418', '278'), ('418', '1088'), ('418', '1102'), ('418', '1103'), ('418', '1107'), ('418', '1111')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('418', 'number', ''), ('418', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('418', '1039442916'), ('418', '1039442915')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

       $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '418' AND `ip` IN ('1039442916', '1039442915')");
       $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '418'");
       $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '418' AND `payment_vendor_id` IN ('278', '1088', '1102', '1103', '1107', '1111')");
       $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '418' AND `payment_method_id` IN ('1', '3', '8')");
       $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '418'");
       $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '418'");
    }
}
