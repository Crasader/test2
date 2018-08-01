<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171219064508 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (321, 'ChangLian', '暢聯支付', 'https://gateway.beitop.com.cn/controller.action', '0', '', 'payment.https.gateway.92up.cn', '', 'ChangLian', '1', '0', '0', '1', '224', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('321', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('321', '1'), ('321', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('321', '1'), ('321', '2'), ('321', '3'), ('321', '4'), ('321', '5'), ('321', '6'), ('321', '8'), ('321', '9'), ('321', '10'), ('321', '11'), ('321', '12'), ('321', '13'), ('321', '14'), ('321', '15'), ('321', '16'), ('321', '17'), ('321', '19'), ('321', '1090'), ('321', '1103')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('321', 'number', ''), ('321', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('321', '661267873'), ('321', '794742243'), ('321', '661293969'), ('321', '661294104'), ('321', '661294632'), ('321', '661294386'), ('321', '661293149')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '321' AND `ip` IN ('661267873', '794742243', '661293969', '661294104', '661294632', '661294386', '661293149')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '321'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '321' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '1090', '1103')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '321' AND `payment_method_id` IN ('1', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '321'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '321'");
    }
}
