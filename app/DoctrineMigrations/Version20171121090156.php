<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171121090156 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (293, 'ZBPay2', '眾寶2.0', 'https://gateway.zbpay365.com/GateWay/Pay', '0', '', '', '', 'ZBPay2', '1', '0', '0', '1', '197', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('293', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('293', '1'), ('293', '3'), ('293', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('293', '1'), ('293', '2'), ('293', '3'), ('293', '4'), ('293', '5'), ('293', '6'), ('293', '8'), ('293', '9'), ('293', '10'), ('293', '11'), ('293', '12'), ('293', '14'), ('293', '16'), ('293', '17'), ('293', '220'), ('293', '223'), ('293', '226'), ('293', '228'), ('293', '1090'), ('293', '1092'), ('293', '1097'), ('293', '1098'), ('293', '1103'), ('293', '1104'), ('293', '1107'), ('293', '1108')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('293', 'number', ''), ('293', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('293', '225617670'), ('293', '910189519')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '293' AND `ip` IN ('225617670', '910189519')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '293'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '293' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '14', '16', '17', '220', '223', '226', '228', '1090', '1092', '1097', '1098', '1103', '1104', '1107', '1108')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '293' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '293'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '293'");
    }
}
