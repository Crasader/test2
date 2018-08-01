<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171129121010 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (305, 'LiYingPay', '利盈支付', 'http://103.78.122.231:8356/payapi.php', '0', '', 'payment.http.103.78.122.231', '', 'LiYingPay', '1', '0', '0', '1', '208', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('305', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('305', '1'), ('305', '3'), ('305', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('305', '1'), ('305', '2'), ('305', '3'), ('305', '4'), ('305', '5'), ('305', '6'), ('305', '8'), ('305', '9'), ('305', '10'), ('305', '11'), ('305', '12'), ('305', '13'), ('305', '14'), ('305', '15'), ('305', '16'), ('305', '17'), ('305', '1090'), ('305', '1092'), ('305', '1097'), ('305', '1103'), ('305', '1104'), ('305', '1107')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('305', 'number', ''), ('305', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('305', '1733196518'), ('305', '1733196519'), ('305', '1733196521')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '305' AND `ip` IN ('1733196518', '1733196519', '1733196521')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '305'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '305' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '1090', '1092', '1097', '1103', '1104', '1107')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '305' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '305'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '305'");
    }
}
