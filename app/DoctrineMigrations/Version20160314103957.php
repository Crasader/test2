<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160314103957 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `bank_info` (`id`, `bankname`, `abbr`, `bank_url`, `virtual`, `withdraw`, `enable`) VALUES ('316', '柳州银行', '柳州', 'https://perbank.bolz.cn/IndexAction.action', '0', '1', '1')");
        $this->addSql("INSERT INTO `payment_vendor` (`id`, `payment_method_id`, `name`) VALUES ('316', '1', '柳州银行')");
        $this->addSql("INSERT INTO `bank_currency` (`id`, `bank_info_id`, `currency`) VALUES ('304', '316', '156')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `bank_currency` WHERE `id` = '304'");
        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` = '316'");
        $this->addSql("DELETE FROM `bank_info` WHERE `id` = '316'");
    }
}
