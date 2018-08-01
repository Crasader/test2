<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180209023703 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET post_url = 'https://pay.ifeepay.com/gateway/pay.jsp', verify_url = 'payment.https.pay.ifeepay.com', withdraw_url = 'https://pay.ifeepay.com/withdraw/singleWithdraw', withdraw_host = 'payment.https.pay.ifeepay.com' WHERE id = '337'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET post_url = 'http://pay.ifeepay.com/gateway/pay.jsp', verify_url = 'payment.http.pay.ifeepay.com', withdraw_url = 'http://pay.ifeepay.com/withdraw/singleWithdraw', withdraw_host = 'payment.http.pay.ifeepay.com' WHERE id = '337'");
    }
}
