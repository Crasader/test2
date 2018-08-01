<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170111154828 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE payment_gateway SET post_url = 'https://trade.hfbpay.com/cgi-bin/netpayment/pay_gate.cgi', reop_url = 'https://trade.hfbpay.com/cgi-bin/netpayment/pay_gate.cgi', verify_url = 'payment.https.trade.hfbpay.com' WHERE id = 108");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE payment_gateway SET post_url = 'http://trade.mokepay.com/cgi-bin/netpayment/pay_gate.cgi', reop_url = 'http://trade.mokepay.com/cgi-bin/netpayment/pay_gate.cgi', verify_url = 'payment.http.trade.mokepay.com' WHERE id = 108");
    }
}
