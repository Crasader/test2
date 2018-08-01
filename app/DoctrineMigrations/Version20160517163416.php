<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160517163416 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO `payment_vendor` (`id`, `payment_method_id`, `name`) VALUES ('1091', '6', 'IAP')");
        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `bind_ip`, `label`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('106', 'IAPIOS', 'Apple-IAP', '', '0', '', 'payment.https.buy.itunes.apple.com', '172.26.54.42', '0', 'IAPIOS', '0', '0', '0', '0', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`currency`, `payment_gateway_id`) VALUES ('156', '106')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('106', '6')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor`(`payment_gateway_id`, `payment_vendor_id`) VALUES ('106','1091')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '106' AND `payment_vendor_id` = '1091'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '106' AND `payment_method_id` = '6'");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '106'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '106'");
        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` = '1091'");
    }
}
