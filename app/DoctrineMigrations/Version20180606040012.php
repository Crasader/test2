<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180606040012 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET post_url = 'https://api.yecimo.com/trade/pay', reop_url = 'https://api.yecimo.com/trade/query', verify_url = 'payment.https.api.yecimo.com', `bind_ip` = '1' WHERE id = 178");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('178', '1098')");
        $this->addSql("INSERT INTO `payment_gateway_bind_ip` (`payment_gateway_id`, `ip`) VALUES ('178', '791996988'), ('178', '1964834480'), ('178', '794367886'), ('178', '794367180'), ('178', '791996988')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_bind_ip` WHERE `payment_gateway_id` = '178' AND `ip` IN ('791996988', '1964834480', '794367886', '794367180', '791996988')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '178' AND `payment_vendor_id` = '1098'");
        $this->addSql("UPDATE payment_gateway SET post_url = 'http://api.52hrt.com/trade/pay_v2', reop_url = 'http://api.52hrt.com/trade/query', verify_url = 'payment.http.api.52hrt.com', `bind_ip` = '0' WHERE id = 178");
    }
}
