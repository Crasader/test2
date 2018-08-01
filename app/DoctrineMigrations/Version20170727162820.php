<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170727162820 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE payment_gateway SET post_url = 'http://pay.trx.helipay.com/trx/online/interface.action', reop_url = 'http://pay.trx.helipay.com/trx/online/interface.action', verify_url = 'payment.http.pay.trx.helipay.com' WHERE id = 144");
        $this->addSql("DELETE mlv FROM merchant_level_vendor mlv INNER JOIN merchant m ON mlv.merchant_id = m.id WHERE m.payment_gateway_id = '144' AND mlv.payment_vendor_id = '19'");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '144' AND `payment_vendor_id` = '19'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('144', '19')");
        $this->addSql("UPDATE payment_gateway SET post_url = 'http://api.99juhe.com/trx-service/online/api.action', reop_url = 'http://api.99juhe.com/trx-service/online/api.action', verify_url = 'payment.http.api.99juhe.com' WHERE id = 144");
    }
}
