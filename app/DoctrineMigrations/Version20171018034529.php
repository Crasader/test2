<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171018034529 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET post_url = 'https://gateway.beeepay.com/gateway/payment', reop_url = 'https://query.beeepay.com/gateway/query', verify_url = 'beeepay.com' WHERE id = 187");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('187', '8')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('187', '19'), ('187', '217'), ('187', '220'), ('187', '221'), ('187', '222'), ('187', '228'), ('187', '234'), ('187', '321'), ('187', '361'), ('187', '1090'), ('187', '1092'), ('187', '1103')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '187' AND `payment_vendor_id` IN ('19', '217', '220', '221', '222', '228', '234', '321', '361', '1090', '1092', '1103')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '187' AND `payment_method_id` = '8'");
        $this->addSql("UPDATE payment_gateway SET post_url = 'https://pay.beeepay.com/payment/gateway', reop_url = 'https://pay.beeepay.com/payment/gateway', verify_url = 'payment.https.pay.beeepay.com' WHERE id = 187");
    }
}
