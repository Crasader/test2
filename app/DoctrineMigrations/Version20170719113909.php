<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170719113909 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET withdraw_url = 'http://weivb1.zhifuweishi.com:8080/diy/everySelect/storedProcess/outPayAmtData.jsp', withdraw_host = 'payment.http.weivb1.zhifuweishi.com', withdraw = 1 WHERE id = 65");
        $this->addSql("INSERT INTO payment_gateway_has_bank_info VALUES ('65', '1'), ('65', '2'), ('65', '3'), ('65', '4'), ('65', '5'), ('65', '6'), ('65', '8'), ('65', '9'), ('65', '10'), ('65', '11'), ('65', '12'), ('65', '13'), ('65', '14'), ('65', '15'), ('65', '16'), ('65', '17'), ('65', '19'), ('65', '217'), ('65', '228'), ('65', '308')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `payment_gateway_has_bank_info` WHERE `payment_gateway_id` = '65' AND `bank_info_id` IN ('1', '2', '3', '4', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '19', '217', '228', '308')");
        $this->addSql("UPDATE payment_gateway SET withdraw_url = '', withdraw_host = '', withdraw = 0 WHERE id = 65");
    }
}
