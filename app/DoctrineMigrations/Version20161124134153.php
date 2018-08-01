<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161124134153 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (143, 'YinBangPay', '銀邦支付', 'https://www.yinbangpay.com/gateway/orderPay', '1', 'https://www.yinbangpay.com/gateway/queryPaymentRecord', 'payment.https.www.yinbangpay.com', '', 'YinBangPay', '0', '0', '0', '1', '48', '1')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('143', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('143', '1'), ('143', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('143', '1'), ('143', '2'), ('143', '3'), ('143', '4'), ('143', '5'), ('143', '6'), ('143', '8'), ('143', '9'), ('143', '10'), ('143', '11'), ('143', '12'), ('143', '13'), ('143', '14'), ('143', '15'), ('143', '16'), ('143', '17'), ('143', '19'), ('143', '217'), ('143', '220'), ('143', '222'), ('143', '226'), ('143', '228'), ('143', '233'), ('143', '234'), ('143', '311'), ('143', '312'), ('143', '1090'), ('143', '1092')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('143', 'number', ''), ('143', 'private_key', ''), ('143', 'terId', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '143'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '143' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '220', '222', '226', '228', '233', '234', '311', '312', '1090', '1092')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '143' AND `payment_method_id` IN ('1', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '143'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '143'");
    }
}
