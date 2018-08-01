<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141107103215 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE chip (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, game_code INT UNSIGNED NOT NULL, balance INT NOT NULL, pre_sub INT NOT NULL, pre_add INT NOT NULL, version INT UNSIGNED DEFAULT 1 NOT NULL, INDEX IDX_AA29BCBBA76ED395 (user_id), PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE chip_trans (id BIGINT UNSIGNED NOT NULL, created_at BIGINT UNSIGNED NOT NULL, chip_id INT NOT NULL, user_id INT NOT NULL, game_code INT NOT NULL, opcode INT NOT NULL, amount INT NOT NULL, ref_id BIGINT NOT NULL, checked TINYINT(1) NOT NULL, checked_at DATETIME DEFAULT NULL, commited TINYINT(1) NOT NULL, memo VARCHAR(100) NOT NULL, INDEX idx_chip_trans_ref_id (ref_id), INDEX idx_chip_trans_created_at (created_at), INDEX idx_chip_trans_checked (checked), PRIMARY KEY(id, created_at))");
        $this->addSql("CREATE TABLE chip_entry (id BIGINT UNSIGNED NOT NULL, chip_id INT NOT NULL, user_id INT NOT NULL, game_code INT NOT NULL, opcode INT NOT NULL, amount INT NOT NULL, balance INT NOT NULL, ref_id BIGINT NOT NULL, memo VARCHAR(100) NOT NULL, created_at BIGINT UNSIGNED NOT NULL, chip_version INT UNSIGNED DEFAULT 0 NOT NULL, INDEX idx_chip_entry_user_id_game_at (user_id, game_code, created_at), INDEX idx_chip_entry_created_at (created_at), INDEX idx_chip_entry_user_id_game (user_id, game_code), INDEX idx_chip_entry_opcode (opcode), INDEX idx_chip_entry_ref_id (ref_id), PRIMARY KEY(id))");
        $this->addSql("ALTER TABLE chip ADD CONSTRAINT FK_AA29BCBBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)");
        $this->addSql("INSERT INTO background_process Values ('sync-chip-entry', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '更新籌碼交易明細/週期: 1秒', 0, 0)");
        $this->addSql("INSERT INTO background_process Values ('sync-chip-balance', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '將籌碼資訊同步至資料庫/週期: 1秒', 0, 0)");
        $this->addSql("INSERT INTO background_process Values ('sync-chip-history', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '將交易明細同步至歷史資料庫/週期: 1秒', 0, 0)");
        $this->addSql("INSERT INTO background_process Values ('sync-chip-transaction', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '將交易同步至資料庫/週期: 1秒', 0, 0)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE chip");
        $this->addSql("DROP TABLE chip_trans");
        $this->addSql("DROP TABLE chip_entry");
        $this->addSql("DELETE FROM background_process Where name = 'sync-chip-entry'");
        $this->addSql("DELETE FROM background_process Where name = 'sync-chip-balance'");
        $this->addSql("DELETE FROM background_process Where name = 'sync-chip-history'");
        $this->addSql("DELETE FROM background_process Where name = 'sync-chip-transaction'");
    }
}
