<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171207212836 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (314, 'RuiJieTong', '睿捷通', 'http://119.23.95.79/api/pay.action', '0', '', 'payment.http.119.23.95.79', '', 'RuiJieTong', '1', '0', '0', '1', '217', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('314', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('314', '3'), ('314', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('314', '1090'), ('314', '1092'), ('314', '1097'), ('314', '1098'), ('314', '1103'), ('314', '1104'), ('314', '1107'), ('314', '1108'), ('314', '1109'), ('314', '1111')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('314', 'number', ''), ('314', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('314', '1998020431'), ('314', '2018450782'), ('314', '2018443409'), ('314', '2018427282'), ('314', '2018450941'), ('314', '2018444180')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '314' AND `ip` IN ('1998020431', '2018450782', '2018443409', '2018427282', '2018450941', '2018444180')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '314'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '314' AND `payment_vendor_id` IN ('1090', '1092', '1097', '1098', '1103', '1104', '1107', '1108', '1109', '1111')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '314' AND `payment_method_id` IN ('3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '314'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '314'");
    }
}
