<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161122050727 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE cash_fake_negative (user_id INT NOT NULL, currency SMALLINT UNSIGNED NOT NULL, cash_fake_id INT NOT NULL, balance NUMERIC(16, 4) NOT NULL, negative TINYINT(1) NOT NULL, version INT UNSIGNED NOT NULL, entry_id BIGINT NOT NULL, at BIGINT NOT NULL, opcode INT NOT NULL, amount NUMERIC(16, 4) NOT NULL, entry_balance NUMERIC(16, 4) NOT NULL, ref_id BIGINT DEFAULT 0 NOT NULL, memo VARCHAR(100) DEFAULT \'\' NOT NULL, INDEX idx_cash_fake_negative_negative (negative), PRIMARY KEY(user_id, currency))');
        $this->addSql('CREATE TABLE cash_negative (user_id INT NOT NULL, currency SMALLINT UNSIGNED NOT NULL, cash_id INT NOT NULL, balance NUMERIC(16, 4) NOT NULL, negative TINYINT(1) NOT NULL, version INT UNSIGNED NOT NULL, entry_id BIGINT NOT NULL, at BIGINT NOT NULL, opcode INT NOT NULL, amount NUMERIC(16, 4) NOT NULL, entry_balance NUMERIC(16, 4) NOT NULL, ref_id BIGINT DEFAULT 0 NOT NULL, memo VARCHAR(100) DEFAULT \'\' NOT NULL, INDEX idx_cash_negative_negative (negative), PRIMARY KEY(user_id, currency))');
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `last_end_time`, `memo`, `num`, `msg_num`) VALUES ('sync-cash-fake-negative', 1, '2016-01-01 00:00:00', '2016-01-01 00:00:00', NULL, '同步負數假現金, 1/sec', 0, 0)");
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `last_end_time`, `memo`, `num`, `msg_num`) VALUES ('sync-cash-negative', 1, '2016-01-01 00:00:00', '2016-01-01 00:00:00', NULL, '同步負數現金, 1/sec', 0, 0)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE cash_fake_negative');
        $this->addSql('DROP TABLE cash_negative');
        $this->addSql("DELETE FROM background_process WHERE name = 'sync-cash-fake-negative'");
        $this->addSql("DELETE FROM background_process WHERE name = 'sync-cash-negative'");
    }
}
