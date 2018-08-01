<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171003061723 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`) VALUES (250, 'YueBaoPay', '月寶支付', 'http://gateway.yuebaopay.cn/online/gateway', 0, '', '', '', 'YueBaoPay', 0, 0, '0', '1', '154', '0')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES ('250', '156')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('250', '1'), ('250', '3'), ('250', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('250' ,'1'), ('250' ,'2'), ('250' ,'3'), ('250' ,'4'), ('250' ,'5'), ('250' ,'6'), ('250' ,'7'), ('250' ,'8'), ('250' ,'9'), ('250' ,'10'), ('250' ,'11'), ('250' ,'12'), ('250' ,'13'), ('250' ,'14'), ('250' ,'15'), ('250' ,'16'), ('250' ,'17'), ('250' ,'19'), ('250' ,'217'), ('250' ,'222'), ('250' ,'223'), ('250' ,'226'), ('250' ,'228'), ('250' ,'233'), ('250' ,'234'), ('250' ,'297'), ('250' ,'1090'), ('250' ,'1092'), ('250' ,'1097'), ('250' ,'1098'), ('250' ,'1099'), ('250' ,'1103'), ('250' ,'1104')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('250', 'number', ''), ('250', 'private_key', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '250'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '250' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '222', '223', '226', '228', '233', '234', '297', '1090', '1092', '1097', '1098', '1099', '1103', '1104')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '250' AND `payment_method_id` IN ('1', '3', '8')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '250'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '250'");
    }
}
