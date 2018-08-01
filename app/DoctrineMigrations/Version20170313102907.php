<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170313102907 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE chip");
        $this->addSql("DROP TABLE chip_entry");
        $this->addSql("DROP TABLE chip_entry_diff");
        $this->addSql("DROP TABLE chip_error");
        $this->addSql("DROP TABLE chip_trans");
        $this->addSql("DELETE FROM `background_process` WHERE `name` = 'sync-chip-entry'");
        $this->addSql("DELETE FROM `background_process` WHERE `name` = 'sync-chip-balance'");
        $this->addSql("DELETE FROM `background_process` WHERE `name` = 'sync-chip-history'");
        $this->addSql("DELETE FROM `background_process` WHERE `name` = 'sync-chip-transaction'");
        $this->addSql("DELETE FROM `background_process` WHERE `name` = 'check-chip-entry'");
        $this->addSql("DELETE FROM `background_process` WHERE `name` = 'check-chip-error'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE chip (id INT UNSIGNED NOT NULL AUTO_INCREMENT, user_id INT NOT NULL, game_code INT UNSIGNED NOT NULL, balance INT NOT NULL, pre_sub INT NOT NULL, pre_add INT NOT NULL, version INT UNSIGNED NOT NULL DEFAULT '1', PRIMARY KEY (id), INDEX `IDX_AA29BCBBA76ED395` (user_id))");
        $this->addSql("ALTER TABLE chip ADD CONSTRAINT FK_AA29BCBBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)");
        $this->addSql("CREATE TABLE chip_entry (id BIGINT UNSIGNED NOT NULL, chip_id INT NOT NULL, user_id INT NOT NULL, game_code INT NOT NULL, opcode INT NOT NULL, amount INT NOT NULL, balance INT NOT NULL, ref_id BIGINT NOT NULL, memo VARCHAR(100) NOT NULL, created_at BIGINT UNSIGNED NOT NULL, chip_version INT UNSIGNED NOT NULL DEFAULT '0', PRIMARY KEY (id), INDEX idx_chip_entry_user_id_game_at (user_id, game_code, created_at), INDEX idx_chip_entry_created_at (created_at), INDEX idx_chip_entry_user_id_game (user_id, game_code), INDEX idx_chip_entry_opcode (opcode), INDEX idx_chip_entry_ref_id (ref_id))");
        $this->addSql("CREATE TABLE chip_trans (id BIGINT UNSIGNED NOT NULL, created_at BIGINT UNSIGNED NOT NULL, chip_id INT NOT NULL, user_id INT NOT NULL, game_code INT NOT NULL, opcode INT NOT NULL, amount INT NOT NULL, ref_id BIGINT NOT NULL, checked TINYINT(1) NOT NULL, checked_at DATETIME NULL DEFAULT NULL, commited TINYINT(1) NOT NULL, memo VARCHAR(100) NOT NULL, PRIMARY KEY (id, created_at), INDEX idx_chip_trans_ref_id (ref_id), INDEX idx_chip_trans_created_at (created_at), INDEX idx_chip_trans_checked (checked))");
        $this->addSql("CREATE TABLE chip_entry_diff (id BIGINT NOT NULL, check_time DATETIME NOT NULL, PRIMARY KEY (id))");
        $this->addSql("CREATE TABLE chip_error (id INT NOT NULL AUTO_INCREMENT, chip_id INT NOT NULL, user_id INT NOT NULL, game_code INT NOT NULL, balance INT NOT NULL, total_amount INT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id))");
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `memo`, `num`, `msg_num`) VALUES ('sync-chip-entry', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00', '更新籌碼交易明細/週期: 1秒', 0, 0), ('sync-chip-history', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '將籌碼資訊同步至資料庫/週期: 1秒', 0, 0), ('sync-chip-history', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '將交易明細同步至歷史資料庫/週期: 1秒', 0, 0), ('sync-chip-transaction', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '將交易同步至資料庫/週期: 1秒', 0, 0), ('check-chip-entry', 0, '2015-01-01 00:00:00', '2015-01-01 00:00:00', '檢查籌碼交易明細, 1/hour', 0, 0), ('check-chip-error', 0, '2015-01-01 00:00:00', '2015-01-01 00:00:00', '檢查籌碼明細, 1/hour', 0, 0)");
    }
}
