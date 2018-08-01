<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180502220221 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('456', 'TuDouPay', '土豆支付', 'https://www.aloopay.com/v1/api/ebank/pay', '0', '', 'payment.https.www.aloopay.com', '', 'TuDouPay', '1', '0', '0', '1', '359', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('456', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('456', '1'), ('456', '3'), ('456', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('456', '1'), ('456', '2'), ('456', '3'), ('456', '4'), ('456', '5'), ('456', '6'), ('456', '8'), ('456', '9'), ('456', '10'), ('456', '11'), ('456', '12'), ('456', '14'), ('456', '15'), ('456', '16'), ('456', '17'), ('456', '19'), ('456', '220'), ('456', '222'), ('456', '223'), ('456', '226'), ('456', '228'), ('456', '1092'), ('456', '1098'), ('456', '1103'), ('456', '1104'), ('456', '1107'), ('456', '1111')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('456', 'number', ''), ('456', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('456', '347606393')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '456' AND `ip` = '347606393'");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '456'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '456' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '14', '15', '16', '17', '19', '220', '222', '223', '226', '228', '1092', '1098', '1103', '1104', '1107', '1111')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '456' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '456'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '456'");
    }
}
