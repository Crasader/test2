<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180123072402 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('358', 'RongCaiFu', '融财付', 'https://www.1758pay.com/payment/api/backTransReq', '0', '', '', '', 'RongCaiFu', '1', '0', '0', '1', '261', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('358', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('358', '1'), ('358', '3'), ('358', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('358', '1'), ('358', '2'), ('358', '3'), ('358', '4'), ('358', '5'), ('358', '6'), ('358', '8'), ('358', '9'), ('358', '10'), ('358', '11'), ('358', '12'), ('358', '13'), ('358', '14'), ('358', '15'), ('358', '16'), ('358', '17'), ('358', '19'), ('358', '220'), ('358', '278'), ('358', '1088'), ('358', '1090'), ('358', '1092'), ('358', '1097'), ('358', '1098'), ('358', '1103'), ('358', '1104'), ('358', '1111')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('358', 'number', ''), ('358', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('358', '1950236652'), ('358', '1981748293')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '358' AND `ip` IN ('1950236652', '1981748293')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '358'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '358' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '220', '278', '1088', '1090', '1092', '1097', '1098', '1103', '1104', '1111')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '358' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `payment_gateway_id` = '358' AND `currency` = '156'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '358'");
    }
}
