<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171219090856 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'http://api.pandalive.cn/api/NewPay/Receivablexm', `reop_url` = 'http://api.pandalive.cn/api/Pay/ReceivableItem', `verify_url` = 'payment.http.api.pandalive.cn', `withdraw_url` = 'http://api.pandalive.cn/api/Pay/WithdrawPay', `withdraw_host` = 'payment.http.api.pandalive.cn' where `id` = '202'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_gateway` SET `post_url` = 'http://pay.soulepay.com/Payment/CMBC/submit_netbank', `reop_url` = 'http://120.24.233.221:89/api/Pay/ReceivableItem', `verify_url` = 'payment.http.120.24.233.221', `withdraw_url` = 'http://120.24.233.221:89/api/Pay/WithdrawPay', `withdraw_host` = 'payment.http.120.24.233.221' where `id` = '202'");
    }
}
