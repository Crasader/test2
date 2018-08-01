<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160804141438 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE `payment_gateway` SET `order_id` = `order_id`+1 WHERE `hot` = 1 AND `order_id` > 2 AND `order_id` < 26');
        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `bind_ip`, `label`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('119', 'BaoFooWeiXin', '新寶付微信支付', 'https://public.baofoo.com/platform/gateway/front', '1', 'https://public.baofoo.com/platform/gateway/back', 'payment.https.public.baofoo.com', '', '0', 'BaoFooWeiXin', '0', '0', '1', '3', '1')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('119', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('119', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('119', '1090')");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES ('119', 'number', ''), ('119', 'private_key', ''), ('119', 'terminal_id', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '119'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '119' AND `payment_vendor_id` = '1090'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '119' AND `payment_method_id` = '8'");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `payment_gateway_id` = '119' AND `currency` = '156'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '119'");
        $this->addSql('UPDATE `payment_gateway` SET `order_id` = `order_id`-1 WHERE `hot` = 1 AND `order_id` > 3 AND `order_id` < 27');
    }
}
