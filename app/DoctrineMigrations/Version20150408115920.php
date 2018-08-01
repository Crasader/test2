<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150408115920 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `bind_ip`, `label`, `removed`) VALUES ('89', 'NewGofPay', '新國付寶', 'https://gateway.gopay.com.cn/Trans/WebClientAction.do', '1', 'https://gateway.gopay.com.cn/Trans/WebClientAction.do', 'payment.https.gateway.gopay.com.cn', '172.26.54.2', '0', 'NewGofPay', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('89', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('89', '1')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('89', '1'), ('89', '2'), ('89', '3'), ('89', '4'), ('89', '5'), ('89', '6'), ('89', '8'), ('89', '9'), ('89', '10'), ('89', '11'), ('89', '12'), ('89', '13'), ('89', '14'), ('89', '15'), ('89', '16'), ('89', '17'), ('89', '19'), ('89', '222'), ('89', '226')");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '89' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '222', '226')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '89' AND `payment_method_id` = '1'");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '89'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '89'");
    }
}
