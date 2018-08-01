<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170823073646 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('210', 'ZsagePay', '澤聖支付', 'http://payment.zsagepay.com/ebank/pay.do', '1', 'http://payment.zsagepay.com/ebank/queryOrder.do', 'payment.http.payment.zsagepay.com', '', 'ZsagePay', '1', '0', '0', '1', '114', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('210', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('210', '1'), ('210', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('210', '1'), ('210', '2'), ('210', '3'), ('210', '4'), ('210', '5'), ('210', '6'), ('210', '9'), ('210', '10'), ('210', '11'), ('210', '12'), ('210', '14'), ('210', '15'), ('210', '16'), ('210', '17'), ('210', '1090'), ('210', '1092'), ('210', '1103')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('210', '1998427082'), ('210', '2077188684')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('210', 'number', ''), ('210', 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '210'");
        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '210' AND ip IN ('1998427082', '2077188684')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '210' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '9', '10', '11', '12', '14', '15', '16', '17', '1090', '1092', '1103')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '210' AND `payment_method_id` IN ('1', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '210'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '210'");
    }
}
