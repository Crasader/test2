<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180115045621 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (349, 'F86Pay', 'F86支付', 'https://gw.f86pay.com/native/com.opentech.cloud.easypay.trade.create/0.0.1', '0', '', 'payment.https.gw.f86pay.com', '', 'F86Pay', '1', '0', '0', '1', '252', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES (349, 156)");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('349', '1'), ('349', '3'), ('349', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('349', '278'), ('349', '1088'), ('349', '1090'), ('349', '1092'), ('349', '1097'), ('349', '1098'), ('349', '1103'), ('349', '1104'), ('349', '1107'), ('349', '1108'), ('349', '1111')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('349', 'number', ''), ('349', 'private_key', ''), ('349', 'merchantCerNo', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('349', '791964622'), ('349', '791976218'), ('349', '794453671')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '349' AND `ip` IN ('791964622', '791976218', '794453671')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '349'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '349' AND `payment_vendor_id` IN ('278', '1088', '1090', '1092', '1097', '1098', '1103', '1104', '1107', '1108', '1111')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '349' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '349'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '349'");
    }
}
