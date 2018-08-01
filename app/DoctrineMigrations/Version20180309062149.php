<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180309062149 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('405', 'YiLeXiangPay', '易樂享支付', 'https://open.goodluckchina.net/open/pay/scanCodePayChannel', '0', '', 'payment.https.open.goodluckchina.net', '', 'YiLeXiangPay', '1', '0', '0', '1', '308', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('405', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('405', '1'), ('405', '3'), ('405', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('405', '1'), ('405', '2'), ('405', '3'), ('405', '4'), ('405', '5'), ('405', '6'), ('405', '7'), ('405', '8'), ('405', '10'), ('405', '11'), ('405', '12'), ('405', '13'), ('405', '14'), ('405', '15'), ('405', '17'), ('405', '278'), ('405', '1088'), ('405', '1103')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('405', 'number', ''), ('405', 'private_key', ''), ('405', 'appId', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('405', '463169943'), ('405', '463169954'), ('405', '463169947'), ('405', '463169955')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '405' AND `ip` IN ('463169943', '463169954', '463169947', '463169955')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '405'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '405' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '7', '8', '10', '11', '12', '13', '14', '15', '17', '278', '1088', '1103')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '405' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '405'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '405'");
    }
}
