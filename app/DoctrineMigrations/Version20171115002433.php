<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171115002433 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('285', 'ShuenFuTung', '順付通', 'http://trade.666666pay.cn/cooperate/gateway.cgi', '0', '', 'payment.http.trade.666666pay.cn', '', 'ShuenFuTung', '1', '0', '0', '1', '189', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('285', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('285', '1'), ('285', '3'), ('285', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('285', '1'), ('285', '2'), ('285', '3'), ('285', '4'), ('285', '5'), ('285', '6'), ('285', '8'), ('285', '10'), ('285', '11'), ('285', '12'), ('285', '13'), ('285', '14'), ('285', '15'), ('285', '16'), ('285', '17'), ('285', '217'), ('285', '1090'), ('285', '1092'), ('285', '1097'), ('285', '1103'), ('285', '1104'), ('285', '1111')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('285', 'number', ''), ('285', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('285', '758417209'), ('285', '791960088')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '285' AND `ip` IN ('758417209', '791960088')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '285'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '285' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '10', '11', '12', '13', '14', '15', '16', '17', '217', '1090', '1092', '1097', '1103', '1104', '1111')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '285' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '285'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '285'");
    }
}
