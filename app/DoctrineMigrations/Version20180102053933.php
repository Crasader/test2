<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180102053933 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('333', 'RuYiPay', '如一付', 'https://gateway.ruyipay.com/Pay/KDBank.aspx', '0', '', '', '', 'RuYiPay', '1', '0', '0', '1', '236', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('333', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('333', '1'), ('333', '3'), ('333', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('333', '1'), ('333', '2'), ('333', '3'), ('333', '4'), ('333', '5'), ('333', '6'), ('333', '8'), ('333', '9'), ('333', '10'), ('333', '11'), ('333', '12'), ('333', '13'), ('333', '14'), ('333', '15'), ('333', '16'), ('333', '17'), ('333', '19'), ('333', '217'), ('333', '220'), ('333', '221'), ('333', '222'), ('333', '223'), ('333', '226'), ('333', '228'), ('333', '233'), ('333', '234'), ('333', '1090'), ('333', '1097'), ('333', '1103'), ('333', '1104'), ('333', '1111')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('333', 'number', ''), ('333', 'private_key', '')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('333', '1696941407'), ('333', '2346728836'), ('333', '1696933385'), ('333', '1696941369'), ('333', '794659596')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '333' AND `ip` IN('1696941407', '2346728836', '1696933385', '1696941369', '794659596')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '333'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '333' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '220', '221', '222', '223', '226', '228', '233', '234', '1090', '1097', '1103', '1104', '1111')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '333' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '333'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '333'");
    }
}
