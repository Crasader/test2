<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171127155813 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET auto_reop = 1, reop_url = 'https://query.debaozhifu.com/query', verify_url = 'payment.https.query.debaozhifu.com' WHERE id = 273");
        $this->addSql("UPDATE payment_gateway SET auto_reop = 1, reop_url = 'https://query.5wpay.net/query', verify_url = 'payment.https.query.5wpay.net' WHERE id = 274");
        $this->addSql("UPDATE payment_gateway SET auto_reop = 1, reop_url = 'https://query.zfhuipay.com/query', verify_url = 'payment.https.query.zfhuipay.com' WHERE id = 292");

    }

    /**
     * @param Schema $schema?
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET auto_reop = 0, reop_url = '', verify_url = '' WHERE id = 292");
        $this->addSql("UPDATE payment_gateway SET auto_reop = 0, reop_url = '', verify_url = '' WHERE id = 274");
        $this->addSql("UPDATE payment_gateway SET auto_reop = 0, reop_url = '', verify_url = '' WHERE id = 273");
    }
}
