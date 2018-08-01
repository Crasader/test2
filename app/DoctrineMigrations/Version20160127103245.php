<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160127103245 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `bank_info` SET `bankname` = '微信支付__二維', `abbr` = '微信二維' WHERE `id` = '296'");
        $this->addSql("UPDATE `payment_vendor` SET `name` = '微信支付__二維' WHERE `id` = '296'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE `payment_vendor` SET `name` = '微信支付' WHERE `id` = '296'");
        $this->addSql("UPDATE `bank_info` SET `bankname` = '微信支付', `abbr` = '微信' WHERE `id` = '296'");
    }
}
