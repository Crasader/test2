<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170904094119 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('217', 'SinFu', '信付', 'http://pay.187pay.com/bank/', '0', '', '', '', 'SinFu', '0', '0', '0', '1', '121', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('217', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('217', '1'), ('217', '3'), ('217', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('217', '1'), ('217', '2'), ('217', '3'), ('217', '4'), ('217', '5'), ('217', '6'), ('217', '8'), ('217', '9'), ('217', '10'), ('217', '11'), ('217', '12'), ('217', '13'), ('217', '14'), ('217', '15'), ('217', '16'), ('217', '17'), ('217', '19'), ('217', '217'), ('217', '222'), ('217', '223'), ('217', '226'), ('217', '228'), ('217', '234'), ('217', '1090'), ('217', '1092'), ('217', '1097'), ('217', '1098'), ('217', '1103'), ('217', '1104'), ('217', '1107'), ('217', '1108'), ('217', '1109'), ('217', '1110')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('217', 'number', ''), ('217', 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '217'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '217' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '222', '223', '226', '228', '234', '1090', '1092', '1097', '1098', '1103', '1104', '1107', '1108', '1109', '1110')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '217' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '217'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '217'");
    }
}
