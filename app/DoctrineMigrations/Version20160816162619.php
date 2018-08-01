<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160816162619 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (122, 'Okfpay', 'OK付', 'https://gateway.okfpay.com/Gate/payindex.aspx', '1', 'https://gateway.okfpay.com/Gate/Search.ashx', 'payment.https.gateway.okfpay.com', '', 'Okfpay', '0', '0', '0', '1', '31', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES (122, 156)");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('122', '1'), (122, 8)");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('122', '1'), ('122', '2'), ('122', '3'), ('122', '4'), ('122', '5'), ('122', '6'), ('122', '8'), ('122', '9'), ('122', '10'), ('122', '11'), ('122', '12'), ('122', '13'), ('122', '14'), ('122', '15'), ('122', '16'), ('122', '17'), ('122', '19'), ('122', '217'), ('122', '228'), ('122', '278'), ('122', '1090'), ('122', '1092')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('122', 'number', ''), ('122', 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '122'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '122' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6',  '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '228', '278', '1090', '1092')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '122' AND `payment_method_id` IN ('1', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '122'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '122'");
    }
}
