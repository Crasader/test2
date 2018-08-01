<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170113110157 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE payment_gateway SET post_url = 'http://api.kcpay.net/PayBank.aspx' WHERE id = 137");
        $this->addSql("UPDATE payment_gateway SET post_url = 'http://api.kcpay.net/PayBank.aspx', verify_url = 'payment.http.api.kcpay.net' WHERE id = 153");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE payment_gateway SET post_url = 'http://api.kcpay.com/PayBank.aspx' WHERE id = 137");
        $this->addSql("UPDATE payment_gateway SET post_url = 'http://api.kcpay.com/PayBank.aspx', verify_url = 'payment.http.api.kcpay.com' WHERE id = 153");
    }
}
