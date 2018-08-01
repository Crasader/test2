<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140728123200 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE coin (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, balance NUMERIC(16, 4) NOT NULL, pre_sub NUMERIC(16, 4) NOT NULL, pre_add NUMERIC(16, 4) NOT NULL, version INT UNSIGNED DEFAULT 1 NOT NULL, INDEX IDX_5569975DA76ED395 (user_id), PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE coin_entry (id BIGINT UNSIGNED NOT NULL, created_at BIGINT UNSIGNED NOT NULL, user_id INT NOT NULL, opcode INT NOT NULL, amount NUMERIC(16, 4) NOT NULL, balance NUMERIC(16, 4) NOT NULL, ref_id BIGINT NOT NULL, memo VARCHAR(100) NOT NULL, INDEX idx_coin_entry_created_at (created_at), INDEX idx_coin_entry_ref_id (ref_id), INDEX idx_coin_entry_opcode (opcode), INDEX idx_coin_entry_user_id (user_id), INDEX idx_coin_entry_user_id_at (user_id, created_at), PRIMARY KEY(id, created_at))");
        $this->addSql("CREATE TABLE coin_trans (id BIGINT UNSIGNED NOT NULL, created_at BIGINT UNSIGNED NOT NULL, user_id INT NOT NULL, opcode INT NOT NULL, amount NUMERIC(16, 4) NOT NULL, ref_id BIGINT NOT NULL, checked TINYINT(1) NOT NULL, checked_at DATETIME DEFAULT NULL, commited TINYINT(1) NOT NULL, memo VARCHAR(100) NOT NULL, INDEX idx_coin_trans_ref_id (ref_id), INDEX idx_coin_trans_created_at (created_at), INDEX idx_coin_trans_checked (checked), PRIMARY KEY(id, created_at))");
        $this->addSql("ALTER TABLE coin ADD CONSTRAINT FK_5569975DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)");
        $this->addSql("INSERT INTO background_process Values ('sync-coin-entry', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '更新點數交易明細/週期: 1秒', 0, 0)");
        $this->addSql("INSERT INTO background_process Values ('sync-coin-balance', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '將點數資訊同步至資料庫/週期: 1秒', 0, 0)");
        $this->addSql("INSERT INTO background_process Values ('sync-coin-history', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '將交易明細同步至歷史資料庫/週期: 1秒', 0, 0)");
        $this->addSql("INSERT INTO background_process Values ('sync-coin-transaction', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '將交易同步至資料庫/週期: 1秒', 0, 0)");
        $this->addSql("CREATE TABLE coin_entry_diff (id BIGINT NOT NULL, check_time DATETIME NOT NULL, PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE coin_error (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, balance NUMERIC(16, 4) NOT NULL, total_amount NUMERIC(16, 4) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id))");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE coin");
        $this->addSql("DROP TABLE coin_entry");
        $this->addSql("DROP TABLE coin_trans");
        $this->addSql("DROP TABLE coin_entry_diff");
        $this->addSql("DROP TABLE coin_error");
        $this->addSql("DELETE FROM background_process Where name = 'sync-coin-entry'");
        $this->addSql("DELETE FROM background_process Where name = 'sync-coin-balance'");
        $this->addSql("DELETE FROM background_process Where name = 'sync-coin-history'");
        $this->addSql("DELETE FROM background_process Where name = 'sync-coin-transaction'");
    }
}
