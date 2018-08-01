<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170828034322 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'http://pay.6bpay.com/PayBank.aspx', `auto_reop` = '0', `reop_url` = '', `verify_url` = '', `upload_key` = '0' WHERE `id` = 152");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_method` (`payment_gateway_id`, `payment_method_id`) VALUES ('152', '1'), ('152', '3')");
        $this->addSql("INSERT INTO `payment_gateway_has_payment_vendor` (`payment_gateway_id`, `payment_vendor_id`) VALUES ('152', '1'), ('152', '2'), ('152', '3'), ('152', '4'), ('152', '5'), ('152', '6'), ('152', '8'), ('152', '9'), ('152', '10'), ('152', '11'), ('152', '12'), ('152', '13'), ('152', '14'), ('152', '15'), ('152', '16'), ('152', '17'), ('152', '19'), ('152', '217'), ('152', '222'), ('152', '223'), ('152', '226'), ('152', '228'), ('152', '233'), ('152', '234'), ('152', '297'), ('152', '1097'), ('152', '1098'), ('152', '1099'), ('152', '1103')");
        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '152' AND `name` IN ('private_key_content', 'public_key_content')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway_description` (payment_gateway_id, name, value) VALUES ('152', 'private_key_content', ''), ('152', 'public_key_content', '')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_vendor` WHERE `payment_gateway_id` = '152' AND `payment_vendor_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '222', '223', '226', '228', '233', '234', '297', '1097', '1098', '1099', '1103')");
        $this->addSql("DELETE FROM `payment_gateway_has_payment_method` WHERE `payment_gateway_id` = '152' AND `payment_method_id` IN ('1', '3')");
        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'https://service.payquickdraw.com/api/gateway', `auto_reop` = '1', `reop_url` = 'https://service.payquickdraw.com/api/gateway', `verify_url` = 'payment.https.service.payquickdraw.com', `upload_key` = '1' WHERE `id` = 152");
    }
}
