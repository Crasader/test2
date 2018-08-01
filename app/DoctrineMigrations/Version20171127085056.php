<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171127085056 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('301', 'YiYunHuei', '易云匯', 'https://api.juhex.com/PayApi/bankPay', '0', '', 'payment.https.api.juhex.com', '', 'YiYunHuei', '1', '0', '0', '1', '205', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('301', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('301', '1'), ('301', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('301', '1'), ('301', '2'), ('301', '3'), ('301', '4'), ('301', '5'), ('301', '6'), ('301', '8'), ('301', '9'), ('301', '10'), ('301', '11'), ('301', '12'), ('301', '13'), ('301', '14'), ('301', '15'), ('301', '16'), ('301', '17'), ('301', '19'), ('301', '217'), ('301', '219'), ('301', '220'), ('301', '221'), ('301', '222'), ('301', '223'), ('301', '226'), ('301', '228'), ('301', '234'), ('301', '278'), ('301', '307'), ('301', '308'), ('301', '309'), ('301', '1090'), ('301', '1092'), ('301', '1103')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('301', 'number', ''), ('301', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('301', '2340543894'), ('301', '2340539987')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '301' AND `ip` IN ('2340543894', '2340539987')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '301'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '301' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '219', '220', '221', '222', '223', '226', '228', '234', '278', '307', '308', '309', '1090', '1092', '1103')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '301' AND `payment_method_id` IN ('1', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '301'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '301'");
    }
}
