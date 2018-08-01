<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180103063544 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (334, 'YuTouPay', '御投支付', 'http://xs.szjietu.com/gateway/bankTrade/prepay ', '0', '', 'payment.http.xs.szjietu.com', '', 'YuTouPay', '0', '0', '0', '1', '237', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES (334, 156)");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('334', '1'), ('334', '3'), ('334', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('334', '1'), ('334', '2'), ('334', '3'), ('334', '4'), ('334', '5'), ('334', '6'), ('334', '8'), ('334', '9'), ('334', '10'), ('334', '11'), ('334', '12'), ('334', '13'), ('334', '14'), ('334', '15'), ('334', '16'), ('334', '17'), ('334', '19'), ('334', '217'), ('334', '221'), ('334', '278'), ('334', '308'), ('334', '311'), ('334', '1090'), ('334', '1092'), ('334', '1097'), ('334', '1098'), ('334', '1104')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('334', 'number', ''), ('334', 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '334'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '334' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '221', '278', '308', '311', '1090', '1092', '1097', '1098', '1104')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '334' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '334'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '334'");
    }
}
