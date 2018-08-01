<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150430155449 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE stat_domain_cash_opcode (id INT UNSIGNED AUTO_INCREMENT NOT NULL, at DATETIME NOT NULL, user_id INT NOT NULL, currency SMALLINT UNSIGNED NOT NULL, domain INT NOT NULL, opcode INT NOT NULL, amount NUMERIC(16, 4) NOT NULL, INDEX idx_stat_domian_cash_opcode_at_user_id (at, user_id), INDEX idx_stat_domian_cash_opcode_domain_at (domain, at), INDEX idx_stat_domain_cash_opcode_opcode_at (opcode, at), PRIMARY KEY(id))");
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `memo`, `num`, `msg_num`, `last_success`) VALUES ('stat-domain-cash-opcode', 1, '2015-01-01 00:00:00', '2015-01-01 00:00:00', '轉統計會員現金交易明細, 每天12:00', 0, 0, '2015-01-01 00:00:00')");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE stat_domain_cash_opcode");
        $this->addSql("DELETE FROM background_process WHERE name = 'stat-domain-cash-opcode'");
    }
}
