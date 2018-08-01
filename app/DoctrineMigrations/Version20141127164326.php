<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141127164326 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.https.www.baokim.vn', `post_url` = 'payment.http.sandbox.baokim.vn' WHERE `id` = '31'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.https.www.gopay.com.cn' WHERE `id` = '34'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.http.do.jftpay.net' WHERE `id` = '38'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.https.cloud1.semanticweb.cn' WHERE `id` = '65'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.http.interface.reapal.com' WHERE `id` = '75'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.https.api.uipas.com' WHERE `id` = '78'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.https.www.nganluong.vn' WHERE `id` = '79'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'payment.https.api.neteller.com' WHERE `id` = '80'");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'www.baokim.vn', `post_url` = 'payment.http.sandbox.baokim.vn/services/payment_pro_2/init?wsdl' WHERE `id` = '31'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'www.gopay.com.cn' WHERE `id` = '34'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'do.jftpay.net' WHERE `id` = '38'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'cloud1.semanticweb.cn' WHERE `id` = '65'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'interface.reapal.com' WHERE `id` = '75'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'api.uipas.com/apiv2/index/wsdl' WHERE `id` = '78'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'www.nganluong.vn/micro_checkout_api.php?wsdl' WHERE `id` = '79'");
        $this->addSql("UPDATE `payment_gateway` SET `verify_url` = 'api.neteller.com' WHERE `id` = '80'");
    }
}
