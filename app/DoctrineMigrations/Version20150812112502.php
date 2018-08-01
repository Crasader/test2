<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150812112502 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`) VALUES (94, 'Befpay', '幣付寶', 'http://i.5dd.com/pay.api', 1, 'http://www.5dd.com/frontpage/OrderInfo', 'payment.http.www.5dd.com', '172.26.54.3', 'Befpay', 0, 0)");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES (94, 156)");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('94', '1')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('94', '1'), ('94', '2'), ('94', '3'), ('94', '4'), ('94', '5'), ('94', '6'), ('94', '8'), ('94', '9'), ('94', '10'), ('94', '11'), ('94', '12'), ('94', '13'), ('94', '14'), ('94', '15'), ('94', '16'), ('94', '17'), ('94', '220'), ('94', '221'), ('94', '222'), ('94', '223'), ('94', '226'), ('94', '234')");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '94' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '220', '221', '222', '223', '226', '234')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '94' AND `payment_method_id` = '1'");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '94'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '94'");
    }
}
