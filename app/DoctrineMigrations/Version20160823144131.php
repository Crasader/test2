<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160823144131 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE `payment_gateway` SET `order_id` = `order_id`+1 WHERE `hot` = 1 AND `order_id` > 32 AND `order_id` < 34');
        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `bind_ip`, `label`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('124', 'NewEhking', '易匯金二代', 'https://api.ehking.com/onlinePay/order', '1', 'https://api.ehking.com/onlinePay/query', 'payment.https.api.ehking.com', '', '1', 'NewEhking', '0', '0', '1', '33', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('124', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('124', '1'), ('124', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('124', '1'), ('124', '2'), ('124', '3'), ('124', '4'), ('124', '5'), ('124', '6'), ('124', '7'), ('124', '8'), ('124', '9'), ('124', '10'), ('124', '11'), ('124', '12'), ('124', '13'), ('124', '14'), ('124', '15'), ('124', '16'), ('124', '17'), ('124', '19'), ('124', '1090'), ('124', '1092')");
        $this->addSql("INSERT INTO `payment_gateway_description` (payment_gateway_id, name, value) VALUES ('124', 'number', ''), ('124', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('124', '1931326776'), ('124', '2032762350')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '124' AND ip IN ('1931326776', '2032762350')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '124'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '124' AND `payment_vendor_id` in ('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '1090', '1092')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '124' AND `payment_method_id` in ('1', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `payment_gateway_id` = '124' AND `currency` = '156'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '124'");
        $this->addSql('UPDATE `payment_gateway` SET `order_id` = `order_id`-1 WHERE `hot` = 1 AND `order_id` > 33 AND `order_id` < 35');
    }
}
