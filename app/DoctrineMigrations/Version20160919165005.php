<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160919165005 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `bind_ip`, `label`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('128', 'Miaofu', '秒付', 'http://pay.miaofupay.com/gateway', '1', 'http://pay.miaofupay.com/query', 'payment.http.pay.miaofupay.com', '', '0', 'Miaofu', '0', '0', '1', '34', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('128', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('128', '1'), ('128', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('128', '1'), ('128', '2'), ('128', '3'), ('128', '4'), ('128', '5'), ('128', '6'), ('128', '8'), ('128', '9'), ('128', '10'), ('128', '11'), ('128', '12'), ('128', '13'), ('128', '14'), ('128', '15'), ('128', '16'), ('128', '17'), ('128', '19'), ('128', '228'), ('128', '234'), ('128', '1090'), ('128', '1092')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('128', 'number', ''), ('128', 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '128'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '128' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '228', '234', '1090', '1092')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '128' AND `payment_method_id` IN ('1', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `payment_gateway_id` = '128' AND `currency` = '156'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '128'");
    }
}
