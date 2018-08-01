<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170209122631 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('163', 'HaoFuPay', '好付支付', 'https://gate.xiaojd160.com/gateway', '1', 'https://gate.xiaojd160.com/query', 'payment.https.gate.xiaojd160.com', '', 'HaoFuPay', '0', '0', '0', '1', '68', '1')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('163', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('163', '1'), ('163', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('163', '1'), ('163', '2'), ('163', '3'), ('163', '4'), ('163', '5'), ('163', '6'), ('163', '8'), ('163', '9'), ('163', '10'), ('163', '11'), ('163', '12'), ('163', '13'), ('163', '14'), ('163', '15'), ('163', '16'), ('163', '17'), ('163', '19'), ('163', '217'), ('163', '219'), ('163', '222'), ('163', '223'), ('163', '224'), ('163', '225'), ('163', '226'), ('163', '227'), ('163', '228'), ('163', '229'), ('163', '230'), ('163', '231'), ('163', '232'), ('163', '312'), ('163', '1090'), ('163', '1092')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('163', 'number', ''), ('163', 'private_key', ''), ('163', 'private_key_content', ''), ('163', 'public_key_content', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '163'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '163' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '219', '222', '223', '224', '225', '226', '227', '228', '229', '230', '231', '232', '312', '1090', '1092')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '163' AND `payment_method_id` IN ('1', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '163'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '163'");
    }
}
