<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140902135337 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO `bank_info` (`id`, `bankname`, `abbr`, `bank_url`, `virtual`, `withdraw`, `enable`) VALUES ('282', 'UIPAS', 'UIPAS', 'https://www.uipas.com/', '0', '0', '1')");
        $this->addSql("INSERT INTO `payment_vendor` (`id`, `payment_method_id`, `name`) VALUES ('282', '1', 'UIPAS')");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` = '282'");
        $this->addSql("DELETE FROM `bank_info` WHERE `id` = '282'");
    }
}
