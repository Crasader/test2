<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180323060503 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `code` = 'FuJuBaoPay', `name` = '付聚宝', `post_url` = 'fujubaopay.com', `reop_url` = 'https://query.fujubaopay.com/order/query', `verify_url` = 'payment.https.query.fujubaopay.com', `label` = 'FuJuBaoPay', `bind_ip` = 1 WHERE `id` = 190");
        $this->addSql("DELETE mlv FROM `merchant_level_vendor` mlv INNER JOIN `merchant` m ON m.`id` = mlv.`merchant_id` WHERE m.`payment_gateway_id` = 190 AND mlv.`payment_vendor_id` IN ('1090', '1092', '1097')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = 190 AND `payment_vendor_id` IN ('1090', '1092', '1097')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('190', '1111')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('190', '793462093')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '190' AND `ip` = '793462093'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = 190 AND `payment_vendor_id` = '1111'");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('190', '1090'), ('190', '1092'), ('190', '1097')");
        $this->addSql("UPDATE `payment_gateway` SET `code` = 'UPay', `name` = 'U付', `post_url` = 'uyinpay.com', `reop_url` = 'https://query.uyinpay.com/order/query', `verify_url` = 'payment.https.query.uyinpay.com', `label` = 'UPay', `bind_ip` = 0 WHERE `id` = 190");
    }
}
