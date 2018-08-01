<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170728041136 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `label`, `bind_ip`, `removed`, `withdraw`, `hot`, `order_id`, `upload_key`, `deposit`, `mobile`, `withdraw_url`, `withdraw_host`) VALUES (199, 'DinDin', 'DINDIN', '', '0', '', '', '', 'DinDin', '0', '0', '1', '0', '71', '0', '0', '0', 'https://payout.sdapayapi.com/8001/Customer.asmx/GetFund', 'payment.https.payout.sdapayapi.com')");
        $this->addSql("INSERT INTO `payment_gateway_currency` (`payment_gateway_id`, `currency`) VALUES (199, 156)");
        $this->addSql("INSERT INTO `payment_gateway_has_bank_info` (`payment_gateway_id`, `bank_info_id`) VALUES ('199', '1'), ('199', '2'), ('199', '3'), ('199', '4'), ('199', '5'), ('199', '6'), ('199', '8'), ('199', '9'), ('199', '10'), ('199', '11'), ('199', '12'), ('199', '13'), ('199', '14'), ('199', '15'), ('199', '16'), ('199', '17'), ('199', '19'), ('199', '217'), ('199', '218'), ('199', '219'), ('199', '220'), ('199', '221'), ('199', '234')");
        $this->addSql("INSERT INTO `payment_gateway_description` (`payment_gateway_id`, `name`, `value`) VALUES ('199', 'number', ''), ('199', 'private_key', ''), ('199', 'ID', ''), ('199', 'key2', ''), ('199', 'CardNum', '')");

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_description` WHERE `payment_gateway_id` = '199'");
        $this->addSql("DELETE FROM `payment_gateway_has_bank_info` WHERE `payment_gateway_id` = '199' AND `bank_info_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '218', '219', '220', '221', '234')");
        $this->addSql("DELETE FROM `payment_gateway_currency` WHERE `currency` = '156' AND `payment_gateway_id` = '199'");
        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` = '199'");
    }
}
