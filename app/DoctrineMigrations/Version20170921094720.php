<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170921094720 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('243', 'ChengHsinPay', '誠信支付', 'http://47.52.25.147:8070/payment/PayApply.do', '0', '', 'payment.http.47.52.25.147', '', 'ChengHsinPay', '0', '0', '0', '1', '147', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('243', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('243', '1'), ('243', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('243', '1'), ('243', '2'), ('243', '3'), ('243', '4'), ('243', '5'), ('243', '6'), ('243', '8'), ('243', '10'), ('243', '11'), ('243', '12'), ('243', '13'), ('243', '14'), ('243', '15'), ('243', '16'), ('243', '17'), ('243', '217'), ('243', '1090'), ('243', '1092'), ('243', '1103')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('243', 'number', ''), ('243', 'private_key', ''), ('243', 'receivableType', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '243'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '243' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '10', '11', '12', '13', '14', '15', '16', '17', '217', '1090', '1092', '1103')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '243' AND `payment_method_id` IN ('1', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '243'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '243'");
    }
}
