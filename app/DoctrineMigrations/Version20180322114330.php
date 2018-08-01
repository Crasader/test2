<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180322114330 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('415', 'XgxPay', '新干线支付', 'https://www.xgxpay.com/Pay', '0', '', '', '', 'XgxPay', '1', '0', '0', '1', '318', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('415', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('415', '1'), ('415', '3'), ('415', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('415', '1'), ('415', '2'), ('415', '3'), ('415', '4'), ('415', '5'), ('415', '6'), ('415', '8'), ('415', '9'), ('415', '10'), ('415', '11'), ('415', '12'), ('415', '13'), ('415', '14'), ('415', '15'), ('415', '16'), ('415', '17'), ('415', '19'), ('415', '217'), ('415', '222'), ('415', '223'), ('415', '226'), ('415', '228'), ('415', '278'), ('415', '308'), ('415', '312'), ('415', '321'), ('415', '1088'), ('415', '1092'), ('415', '1103'), ('415', '1111')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('415', 'number', ''), ('415', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('415', '1744044082'), ('415', '763061409')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '415' AND `ip` IN ('1744044082', '763061409')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '415'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '415' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '222', '223', '226', '228', '278', '308', '312', '321', '1088', '1092', '1103', '1111')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '415' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '415'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '415'");
    }
}
