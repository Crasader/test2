<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170821023316 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('208', 'ZTuoPay', '掌托支付', 'http://pay.palmrestpay.com/online/gateway.html', '1', 'http://pay.palmrestpay.com/online/gateway.html', 'payment.http.pay.palmrestpay.com', '', 'ZTuoPay', '0', '0', '0', '1', '112', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('208', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('208', '1'), ('208', '3'), ('208', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('208', '1'), ('208', '2'), ('208', '3'), ('208', '4'), ('208', '5'), ('208', '6'), ('208', '8'), ('208', '9'), ('208', '10'), ('208', '11'), ('208', '12'), ('208', '13'), ('208', '14'), ('208', '15'), ('208', '16'), ('208', '17'), ('208', '19'), ('208', '217'), ('208', '222'), ('208', '223'), ('208', '226'), ('208', '228'), ('208', '233'), ('208', '234'), ('208', '1090'), ('208', '1092'), ('208', '1097'), ('208', '1098'), ('208', '1103'), ('208', '1104'), ('208', '1107'), ('208', '1108'), ('208', '1109'), ('208', '1110')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('208', 'number', ''), ('208', 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '208'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '208' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '222', '223', '226', '228', '233', '234', '1090', '1092', '1097', '1098', '1103', '1104', '1107', '1108', '1109', '1110')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '208' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '208'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '208'");
    }
}
