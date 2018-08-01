<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171215072126 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET post_url = 'http://aa.aishundianzi.com/pay234.aspx', reop_url = 'http://aa.aishundianzi.com/PayQuery.aspx', verify_url = 'payment.http.aa.aishundianzi.com' WHERE id = 148");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET post_url = 'http://wx.aishundianzi.com/pay234.aspx', reop_url = 'http://wx.aishundianzi.com/PayQuery.aspx', verify_url = 'payment.http.wx.aishundianzi.com' WHERE id = 148");
    }
}
