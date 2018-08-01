<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170911072841 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET withdraw_url = 'http://weivb1.zhithuachuang.com:8080/diy/everySelect/storedProcess/outPayAmtData.jsp', withdraw_host = 'payment.http.weivb1.zhithuachuang.com' WHERE id = 65");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE payment_gateway SET withdraw_url = 'http://weivb1.zhifuweishi.com:8080/diy/everySelect/storedProcess/outPayAmtData.jsp', withdraw_host = 'payment.http.weivb1.zhifuweishi.com' WHERE id = 65");
    }
}
