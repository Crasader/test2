<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180115084551 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('347', 'BeiFuPay', '貝富支付', 'http://way.yf52.com/api/pay', '0', '', '', '', 'BeiFuPay', '1', '0', '0', '1', '250', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('347', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('347', '1'), ('347', '3'), ('347', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('347', '1'), ('347', '2'), ('347', '3'), ('347', '4'), ('347', '5'), ('347', '6'), ('347', '8'), ('347', '9'), ('347', '10'), ('347', '11'), ('347', '12'), ('347', '13'), ('347', '14'), ('347', '15'), ('347', '16'), ('347', '17'), ('347', '19'), ('347', '217'), ('347', '220'), ('347', '221'), ('347', '222'), ('347', '223'), ('347', '226'), ('347', '228'), ('347', '233'), ('347', '234'), ('347', '1090'), ('347', '1103'), ('347', '1104')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('347', 'number', ''), ('347', 'private_key', ''), ('347', 'desKey', ''), ('347', 'pSyspwd', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('347', '1992412371'), ('347', '1992412373'), ('347', '1992412377')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '347' AND `ip` IN('1992412371', '1992412373', '1992412377')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '347'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '347' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '220', '221', '222', '223', '226', '228', '233', '234', '1090', '1103', '1104')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '347' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '347'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '347'");
    }
}
