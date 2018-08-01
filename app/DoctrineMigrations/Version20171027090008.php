<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171027090008 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES ('268', 'Pay32', '32pay', 'https://api.32pay.com/Pay/KDSubmitUrl.aspx', '0', '', 'payment.https.api.32pay.com', '', 'Pay32', '0', '0', '0', '1', '172', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('268', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('268', '1'), ('268', '3'), ('268', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('268', '1'), ('268', '2'), ('268', '3'), ('268', '4'), ('268', '5'), ('268', '6'), ('268', '7'), ('268', '8'), ('268', '9'), ('268', '10'), ('268', '11'), ('268', '12'), ('268', '13'), ('268', '14'), ('268', '15'), ('268', '16'), ('268', '17'), ('268', '19'), ('268', '217'), ('268', '220'), ('268', '221'), ('268', '222'), ('268', '223'), ('268', '226'), ('268', '228'), ('268', '233'), ('268', '234'), ('268', '297'), ('268', '1090'), ('268', '1092'), ('268', '1097'), ('268', '1098'), ('268', '1103'), ('268', '1104'), ('268', '1107'), ('268', '1109')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('268', 'number', ''), ('268', 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '268'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '268' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '220', '221', '222', '223', '226', '228', '233', '234', '297', '1090', '1092', '1097', '1098', '1103', '1104', '1107', '1109')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '268' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '268'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '268'");
    }
}
