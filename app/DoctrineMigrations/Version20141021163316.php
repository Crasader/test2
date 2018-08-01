<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141021163316 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO `bank_info` (`id`, `bankname`, `abbr`, `bank_url`, `virtual`, `withdraw`, `enable`) VALUES ('293', 'Paypal', 'Paypal', 'http://www.paypal.com/', '0', '0', '1')");
        $this->addSql("INSERT INTO `payment_vendor` (`id`, `payment_method_id`, `name`) VALUES ('293', '1', 'Paypal')");
        $this->addSql("ALTER TABLE merchant CHANGE number number VARCHAR(80) NOT NULL");
        $this->addSql("ALTER TABLE cash_deposit_entry CHANGE merchant_number merchant_number VARCHAR(80) NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` = '293'");
        $this->addSql("DELETE FROM `bank_info` WHERE `id` = '293'");
        $this->addSql("ALTER TABLE merchant CHANGE number number VARCHAR(25) NOT NULL");
        $this->addSql("ALTER TABLE cash_deposit_entry CHANGE merchant_number merchant_number VARCHAR(25) NOT NULL");
    }
}
