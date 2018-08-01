<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140904144730 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO `bank_info` (`id`, `bankname`, `abbr`, `bank_url`, `virtual`, `withdraw`, `enable`) VALUES ('283', 'SHB', 'SHB', '', '0', '0', '1'), ('284', 'SEABANK', 'SEABANK', '', '0', '0', '1'), ('285', 'TIENPHONG BANK', 'TIENPHONG BANK', '', '0', '0', '1'), ('286', 'VPBANK', 'VPBANK', '', '0', '0', '1'), ('287', 'BAC A BANK', 'BAC A BANK', '', '0', '0', '1'), ('288', 'GP BANK', 'GP BANK', '', '0', '0', '1'), ('289', 'DAI A BANK', 'DAI A BANK', '', '0', '0', '1'), ('290', 'OCB', 'OCB', '', '0', '0', '1'), ('291', 'Ngan Luong', 'Ngan Luong', 'https://www.nganluong.vn/', '1', '0', '1')");
        $this->addSql("INSERT INTO `payment_vendor` (`id`, `payment_method_id`, `name`) VALUES ('283', '1', 'SHB'), ('284', '1', 'SEABANK'), ('285', '1', 'TIENPHONG BANK'), ('286', '1', 'VPBANK'), ('287', '1', 'BAC A BANK'), ('288', '1', 'GP BANK'), ('289', '1', 'DAI A BANK'), ('290', '1', 'OCB'), ('291', '1', 'Ngan Luong')");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM `payment_vendor` WHERE `id` IN (283, 284, 285, 286, 287, 288, 289, 290, 291)");
        $this->addSql("DELETE FROM `bank_info` WHERE `id` IN (283, 284, 285, 286, 287, 288, 289, 290, 291)");
    }
}
