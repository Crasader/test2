<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160309100616 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE stat_cash_opcode_hk (id INT UNSIGNED AUTO_INCREMENT NOT NULL, at DATETIME NOT NULL, user_id INT NOT NULL, currency SMALLINT UNSIGNED NOT NULL, domain INT NOT NULL, parent_id INT NOT NULL, opcode INT NOT NULL, amount NUMERIC(16, 4) NOT NULL, count INT NOT NULL, INDEX idx_stat_cash_opcode_hk_at_user_id (at, user_id), INDEX idx_stat_cash_opcode_hk_domain_at (domain, at), INDEX idx_stat_cash_opcode_hk_opcode_at (opcode, at), PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE stat_domain_cash_opcode_hk (id INT UNSIGNED AUTO_INCREMENT NOT NULL, at DATETIME NOT NULL, user_id INT NOT NULL, currency SMALLINT UNSIGNED NOT NULL, domain INT NOT NULL, opcode INT NOT NULL, amount NUMERIC(16, 4) NOT NULL, INDEX idx_stat_domian_cash_opcode_hk_at_user_id (at, user_id), INDEX idx_stat_domian_cash_opcode_hk_domain_at (domain, at), INDEX idx_stat_domian_cash_opcode_hk_opcode_at (opcode, at), PRIMARY KEY(id))');
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `last_end_time`, `memo`, `num`, `msg_num`) VALUES ('stat-cash-opcode-hk', 1, '2016-01-01 00:00:00', '2016-01-01 00:00:00', '2016-01-01 00:00:00', '轉香港時區統計現金交易明細, 每天12:30', 0, 0)");
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `last_end_time`, `memo`, `num`, `msg_num`) VALUES ('stat-domain-cash-opcode-hk', 1, '2016-01-01 00:00:00', '2016-01-01 00:00:00', '2016-01-01 00:00:00', '轉香港時區統計會員現金交易明細, 每天12:30', 0, 0)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE stat_cash_opcode_hk');
        $this->addSql('DROP TABLE stat_domain_cash_opcode_hk');
        $this->addSql("DELETE FROM background_process WHERE name = 'stat-cash-opcode-hk'");
        $this->addSql("DELETE FROM background_process WHERE name = 'stat-domain-cash-opcode-hk'");
    }
}
