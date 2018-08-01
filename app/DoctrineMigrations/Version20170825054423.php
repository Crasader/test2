<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170825054423 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO `bank_info` (`id`, `bankname`, `virtual`, `withdraw`, `bank_url`, `abbr`, `enable`) VALUES ('428', 'United Overseas Bank', '0', '1', 'http://www.uob.co.th/', 'UOB', '1')");
        $this->addSql("INSERT INTO `bank_currency` (`id`, `bank_info_id`, `currency`) VALUES ('416', '428', '764')");
        $this->addSql("INSERT INTO `payment_vendor` (`id`, `payment_method_id`, `name`) VALUES ('1112', '1', 'United Overseas Bank')");
        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (211, 'PaySec', 'PaySec', 'https://pay.paysec.com', '1', 'https://pay.paysec.com/GUX/GQueryPayment', 'payment.https.pay.paysec.com', '', 'PaySec', '0', '0', '0', '1', '115', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('211', '156'), ('211', '764')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('211', '1'), ('211', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('211', '1'), ('211', '2'), ('211', '3'), ('211', '4'), ('211', '5'), ('211', '6'), ('211', '8'), ('211', '10'), ('211', '11'), ('211', '12'), ('211', '14'), ('211', '15'), ('211', '16'), ('211', '17'), ('211', '29'), ('211', '30'), ('211', '31'), ('211', '257'), ('211', '258'), ('211', '259'), ('211', '1090'), ('211', '1112')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('211', 'number', ''), ('211', 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '211'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '211' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '10', '11', '12', '14', '15', '16', '17', '29', '30', '31', '257', '258', '259', '1090', '1112')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '211' AND `payment_method_id` IN ('1', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` IN ('156', '764') AND `payment_gateway_id` = '211'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '211'");
        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` = '1112'");
        $this->addSql("DELETE FROM `bank_currency` WHERE `id` = '416'");
        $this->addSql("DELETE FROM `bank_info` WHERE `id` = '428'");
    }
}
