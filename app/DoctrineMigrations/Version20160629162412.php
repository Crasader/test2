<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160629162412 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `bind_ip`, `label`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('114', 'Lfwin', '樂付支付', 'payment.http.api.lfwin.com', '1', '', 'payment.http.api.lfwin.com', '', '1', 'Lfwin', '0', '0', '0', '62', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`currency`, `payment_gateway_id`) VALUES ('156', '114')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('114', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor`(`payment_gateway_id`, `payment_vendor_id`) VALUES ('114', '1090'), ('114', '1092')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('114', '2014957416')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '114' AND `ip` = '2014957416'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '114' AND `payment_vendor_id` IN ('1090', '1092')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '114' AND `payment_method_id` = '8'");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '114'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '114'");
    }
}
