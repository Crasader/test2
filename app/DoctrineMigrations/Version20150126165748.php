<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150126165748 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.https.cha.yeepay.com', `verify_ip` = '172.26.54.3' WHERE `id` = '1'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.https.service.allinpay.com', `verify_ip` = '172.26.54.3' WHERE `id` = '5'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.http.webservice.ips.com.cn', `verify_ip` = '172.26.54.3' WHERE `id` = '8'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.http.pay.beijing.com.cn', `verify_ip` = '172.26.54.3' WHERE `id` = '16'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.http.settle.netpay.sdo.com', `verify_ip` = '172.26.54.3' WHERE `id` = '21'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.https.www.172.com', `verify_ip` = '172.26.54.3' WHERE `id` = '22'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.http.webservice.ips.com.cn', `verify_ip` = '172.26.54.3' WHERE `id` = '23'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.https.www.172.com', `verify_ip` = '172.26.54.3' WHERE `id` = '24'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.https.www.hnapay.com', `verify_ip` = '172.26.54.3' WHERE `id` = '27'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.http.mch.tenpay.com', `verify_ip` = '172.26.54.3' WHERE `id` = '33'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.https.www.epaylinks.cn', `verify_ip` = '172.26.54.3' WHERE `id` = '45'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.http.www.315d.com:9180', `verify_ip` = '172.26.54.3' WHERE `id` = '52'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.http.pay.shunshou.com', `verify_ip` = '172.26.54.3' WHERE `id` = '58'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.http.www.unipaygo.com', `verify_ip` = '172.26.54.3' WHERE `id` = '61'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.https.query.dinpay.com', `verify_ip` = '172.26.54.3' WHERE `id` = '64'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.https.paygate.baofoo.com', `verify_ip` = '172.26.54.3' WHERE `id` = '67'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.http.www.kjb88.com', `verify_ip` = '172.26.54.3' WHERE `id` = '68'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.https.merchant.ecpss.cn', `verify_ip` = '172.26.54.3' WHERE `id` = '71'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.http.www.kjb99.com', `verify_ip` = '172.26.54.3' WHERE `id` = '72'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.https.yintong.com.cn', `verify_ip` = '172.26.54.3' WHERE `id` = '74'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.http.interface.reapal.com', `verify_ip` = '172.26.54.3' WHERE `id` = '75'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.http.payment.kuaiyinpay.com', `verify_ip` = '172.26.54.3' WHERE `id` = '77'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.https.ehkpay.ehking.com', `verify_ip` = '172.26.54.3' WHERE `id` = '85'");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = '', `verify_ip` = '' WHERE `id` IN ('1', '5', '8', '16', '21', '22', '23', '24', '27', '33', '45', '52', '58', '61', '64', '67', '68', '71', '72', '74', '75', '77', '85')");
    }
}
