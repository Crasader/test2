<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180611214408 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('501', 'ChangPay', '畅支付', 'https://n-sdk.retenai.com/api/v1/union.api', '0', '', 'payment.https.n-sdk.retenai.com', '', 'ChangPay', '1', '0', '0', '1', '404', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('501', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('501', '1'), ('501', '3'), ('501', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('501', '1'), ('501', '3'), ('501', '4'), ('501', '6'), ('501', '9'), ('501', '12'), ('501', '16'), ('501', '19'), ('501', '278'), ('501', '1088'), ('501', '1090'), ('501', '1092'), ('501', '1098'), ('501', '1103'), ('501', '1108'), ('501', '1111')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('501', 'number', ''), ('501', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('501', '2641881231'), ('501', '2641881230'), ('501', '922234390'), ('501', '2018476052')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '501' AND `ip` IN ('2641881231', '2641881230', '922234390', '2018476052')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '501'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '501' AND `payment_vendor_id` IN ('1', '3', '4', '6', '9', '12', '16', '19', '278', '1088', '1090', '1092', '1098', '1103', '1108', '1111')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '501' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '501'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '501'");
    }
}
