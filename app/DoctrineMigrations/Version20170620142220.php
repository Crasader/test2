<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170620142220 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET post_url = 'http://api.bin9188.com/api/pay/v2', auto_reop = 0, reop_url = 'http://api.bin9188.com/api/pay/query/v2', verify_url = 'payment.http.api.bin9188.com' WHERE id = 116");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET post_url = 'http://api.fltdmall.cn/api/pay/v2', auto_reop = 1, reop_url = 'http://api.fltdmall.cn/api/pay/query/v2', verify_url = 'payment.http.api.fltdmall.cn' WHERE id = 116");
    }
}
