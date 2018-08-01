<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170816025359 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('205', 'Pay591', '591支付', 'http://pay.pay591.com/bank/', '0', '', '', '', 'Pay591', '0', '0', '0', '1', '109', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('205', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('205', '1'), ('205', '3'), ('205', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('205', '1'), ('205', '2'), ('205', '3'), ('205', '4'), ('205', '5'), ('205', '6'), ('205', '8'), ('205', '9'), ('205', '10'), ('205', '11'), ('205', '12'), ('205', '13'), ('205', '14'), ('205', '15'), ('205', '16'), ('205', '17'), ('205', '19'), ('205', '217'), ('205', '222'), ('205', '223'), ('205', '226'), ('205', '228'), ('205', '234'), ('205', '1090'), ('205', '1092'), ('205', '1097'), ('205', '1098'), ('205', '1103'), ('205', '1104'), ('205', '1107'), ('205', '1108'), ('205', '1109'), ('205', '1110')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('205', 'number', ''), ('205', 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '205'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '205' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '222', '223', '226', '228', '234', '1090', '1092', '1097', '1098', '1103', '1104', '1107', '1108', '1109', '1110')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '205' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '205'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '205'");
    }
}
