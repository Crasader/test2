<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170628121301 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_vendor` (`id`, `payment_method_id`, `name`) VALUES ('1109', '8', '百度錢包__二維'), ('1110', '3', '百度錢包_手機支付')");
        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (191, 'YuanBao', '元寶', 'http://pay.yuanbaozf.com/online/gateway', '1', 'http://pay.yuanbaozf.com/online/gateway', 'payment.http.pay.yuanbaozf.com', '', 'YuanBao', '0', '0', '0', '1', '96', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('191', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('191', '1'), ('191', '3'), ('191', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('191', '1'), ('191', '2'), ('191', '3'), ('191', '4'), ('191', '5'), ('191', '6'), ('191', '7'), ('191', '8'), ('191', '9'), ('191', '10'), ('191', '11'), ('191', '12'), ('191', '13'), ('191', '14'), ('191', '15'), ('191', '16'), ('191', '17'), ('191', '19'), ('191', '217'), ('191', '222'), ('191', '223'), ('191', '226'), ('191', '228'), ('191', '233'), ('191', '234'), ('191', '1090'), ('191', '1092'), ('191', '1097'), ('191', '1098'), ('191', '1103'), ('191', '1104'), ('191', '1107'), ('191', '1108'), ('191', '1109'), ('191', '1110')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('191', 'number', ''), ('191', 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '191'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '191' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '222', '223', '226', '228', '233', '234', '1090', '1092', '1097', '1098', '1103', '1104', '1107', '1108', '1109', '1110')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '191' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '191'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '191'");
        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` IN ('1109', '1110')");
    }
}
