<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160122160231 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO `bank_info` (`id`, `bankname`, `abbr`, `bank_url`, `virtual`, `withdraw`, `enable`) VALUES ('311', '恒丰银行', '恒丰', 'http://www.egbank.com.cn/', '0', '1', '1'), ('312', '成都银行', '成都', 'http://www.bocd.com.cn/', '0', '1', '1')");
        $this->addSql("INSERT INTO `payment_vendor` (`id`, `payment_method_id`, `name`) VALUES ('311', '1', '恒丰银行'), ('312', '1', '成都银行')");
        $this->addSql("INSERT INTO `bank_currency` (`id`, `bank_info_id`, `currency`) VALUES ('299', '311', '156'), ('300', '312', '156')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM `bank_currency` WHERE `id` = '299'");
        $this->addSql("DELETE FROM `bank_currency` WHERE `id` = '300'");
        $this->addSql("DELETE FROM `bank_info` WHERE `id` = '311'");
        $this->addSql("DELETE FROM `bank_info` WHERE `id` = '312'");
        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` = '311'");
        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` = '312'");
    }
}
