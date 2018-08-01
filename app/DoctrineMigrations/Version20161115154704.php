<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161115154704 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (140, 'JubaoPay', '聚寶雲支付', 'https://mapi.jubaopay.com/apipay.htm', '1', 'https://mapi.jubaopay.com/apicheck.htm', 'payment.https.mapi.jubaopay.com', '', 'JubaoPay', '0', '0', '0', '1', '46', '1')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES (140, 156)");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('140', '1'), ('140', '2'), ('140', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('140', '1'), ('140', '2'), ('140', '3'), ('140', '4'), ('140', '5'), ('140', '6'), ('140', '8'), ('140', '9'), ('140', '10'), ('140', '11'), ('140', '12'), ('140', '13'), ('140', '14'), ('140', '15'), ('140', '16'), ('140', '17'), ('140', '19'), ('140', '217'), ('140', '220'), ('140', '221'), ('140', '222'), ('140', '223'), ('140', '224'), ('140', '226'), ('140', '228'), ('140', '234'), ('140', '297'), ('140', '308'), ('140', '309'), ('140', '315'), ('140', '1000'), ('140', '1001'), ('140', '1002'), ('140', '1073'), ('140', '1074'), ('140', '1075'), ('140', '1076'), ('140', '1077'), ('140', '1078'), ('140', '1079'), ('140', '1080'), ('140', '1081'), ('140', '1082'), ('140', '1083'), ('140', '1090'), ('140', '1092')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('140', 'number', ''), ('140', 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '140'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '140' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '220', '221', '222', '223', '224', '226', '228', '234', '297', '308', '309', '315', '1000', '1001', '1002', '1073', '1074', '1075', '1076', '1077', '1078', '1079', '1080', '1081', '1082', '1083', '1090', '1092')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '140' AND `payment_method_id` IN ('1', '2', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '140'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '140'");
    }
}
