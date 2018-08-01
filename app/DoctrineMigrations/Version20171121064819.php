<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171121064819 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('292', 'JrFuHuei', '支付匯', 'zfhuipay.com', '0', '', '', '', 'JrFuHuei', '1', '0', '0', '1', '196', '1')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('292', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('292', '1'), ('292', '3'), ('292', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('292', '1'), ('292', '2'), ('292', '3'), ('292', '4'), ('292', '5'), ('292', '6'), ('292', '8'), ('292', '9'), ('292', '10'), ('292', '11'), ('292', '12'), ('292', '13'), ('292', '14'), ('292', '15'), ('292', '16'), ('292', '17'), ('292', '19'), ('292', '220'), ('292', '222'), ('292', '1090'), ('292', '1092'), ('292', '1097'), ('292', '1103')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('292', 'number', ''), ('292', 'private_key', ''), ('292', 'private_key_content', ''), ('292', 'public_key_content', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('292', '3524664194'), ('292', '1959528817'), ('292', '1902312962'), ('292', '236274674')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '292' AND `ip` IN ('3524664194', '1959528817', '1902312962', '236274674')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '292'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '292' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '220', '222', '1090', '1092', '1097', '1103')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '292' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '292'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '292'");
    }
}
