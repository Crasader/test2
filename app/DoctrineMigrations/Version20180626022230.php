<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180626022230 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `code` = 'YungRenPay', `name` = '永仁支付', `post_url` = 'http://trade.cd178.cn:8880/cgi-bin/netpayment/pay_gate.cgi', `verify_url` = 'payment.http.trade.cd178.cn', `label` = 'YungRenPay' , `bind_ip` = '1', `random_float` = '0' WHERE `id` = '236'");
        $this->addSql("DELETE FROM `payment_gateway_random_float_vendor` WHERE `payment_gateway_id` = '236' AND `payment_vendor_id` IN ('1090', '1092')");
        $this->addSql("DELETE mlv FROM `merchant_level_vendor` mlv INNER JOIN `merchant` m ON m.`id` = mlv.`merchant_id` WHERE m.`payment_gateway_id` = '236' AND mlv.`payment_vendor_id` IN ('1090', '1092', '1103')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '236' AND `payment_vendor_id` IN ('1090', '1092', '1103')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('236', '1111')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('236', '3062039162'), ('236', '3062039163'), ('236', '1708122186'), ('236', '1708122187')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '236' AND `ip` IN ('3062039162', '3062039163', '1708122186', '1708122187')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '236' AND `payment_vendor_id` = '1111'");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('236', '1090'), ('236', '1092'), ('236', '1103')");
        $this->addSql("INSERT INTO `payment_gateway_random_float_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('236', '1090'), ('236', '1092')");
        $this->addSql("UPDATE `payment_gateway` SET `code` = 'SyunLianBao', `name` = '讯联宝', `post_url` = 'http://trade.qnbus.com:8080/cgi-bin/netpayment/pay_gate.cgi', `verify_url` = 'payment.http.trade.qnbus.com', `label` = 'SyunLianBao' , `bind_ip` = '0' , `random_float` = '1' WHERE `id` = '236'");
    }
}
