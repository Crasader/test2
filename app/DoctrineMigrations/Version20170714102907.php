<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170714102907 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE point");
        $this->addSql("DROP TABLE point_entry");
        $this->addSql("DROP TABLE point_title");
        $this->addSql("DROP TABLE point_error");
        $this->addSql("DROP TABLE point_trans");
        $this->addSql("DELETE FROM `background_process` WHERE `name` = 'check-point-error'");
        $this->addSql("DELETE FROM `background_process` WHERE `name` = 'sync-point-balance'");
        $this->addSql("DELETE FROM `background_process` WHERE `name` = 'sync-point-entry'");
        $this->addSql("DELETE FROM `background_process` WHERE `name` = 'sync-point-transaction'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE point (id INT UNSIGNED NOT NULL AUTO_INCREMENT, user_id INT NOT NULL, balance INT NOT NULL, pre_sub INT NOT NULL, pre_add INT NOT NULL, last_entry_at BIGINT NULL DEFAULT NULL, version INT UNSIGNED NOT NULL DEFAULT '1', PRIMARY KEY (id), INDEX IDX_4ED17253A76ED395 (user_id), CONSTRAINT FK_4ED17253A76ED395 FOREIGN KEY (user_id) REFERENCES user (id))");
        $this->addSql("CREATE TABLE point_entry (id BIGINT UNSIGNED NOT NULL, created_at BIGINT UNSIGNED NOT NULL, user_id INT NOT NULL, opcode INT NOT NULL, amount INT NOT NULL, balance INT NOT NULL, ref_id BIGINT NOT NULL, memo VARCHAR(100) NOT NULL, point_version INT UNSIGNED NOT NULL DEFAULT '0', PRIMARY KEY (id, created_at), INDEX idx_point_entry_user_id (user_id), INDEX idx_point_entry_created_at (created_at), INDEX idx_point_entry_ref_id (ref_id), INDEX idx_point_entry_opcode (opcode), INDEX idx_point_entry_user_id_created_at (user_id, created_at))");
        $this->addSql("CREATE TABLE point_error (id INT NOT NULL AUTO_INCREMENT, user_id INT NOT NULL, balance INT NOT NULL, total_amount INT NOT NULL, at DATETIME NOT NULL, PRIMARY KEY (id))");
        $this->addSql("CREATE TABLE point_title (id INT UNSIGNED NOT NULL AUTO_INCREMENT, domain INT NOT NULL, level INT UNSIGNED NOT NULL, name VARCHAR(50) NOT NULL, min_point INT NOT NULL, modified_at DATETIME NOT NULL, PRIMARY KEY (id), UNIQUE INDEX uni_domain_level (domain, level))");
        $this->addSql("CREATE TABLE point_trans (id BIGINT UNSIGNED NOT NULL, created_at BIGINT UNSIGNED NOT NULL, user_id INT NOT NULL, opcode INT NOT NULL, amount INT NOT NULL, ref_id BIGINT NOT NULL, checked TINYINT(1) NOT NULL, checked_at DATETIME NULL DEFAULT NULL, commited TINYINT(1) NOT NULL, memo VARCHAR(100) NOT NULL, PRIMARY KEY (id, created_at), INDEX idx_point_trans_ref_id (ref_id), INDEX idx_point_trans_created_at (created_at), INDEX idx_point_trans_checked (checked))");
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `memo`, `num`, `msg_num`) VALUES ('sync-point-entry', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00', '更新成就點數交易明細/週期: 1秒', 0, 0), ('sync-point-balance', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '將成就點數資訊同步至資料庫/週期: 1秒', 0, 0), ('sync-point-transaction', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '將成就點數的交易同步至資料庫/週期: 1秒', 0, 0), ('check-point-error', 0, '2015-01-01 00:00:00', '2015-01-01 00:00:00', '檢查成就點數明細, 每天07:05', 0, 0)");
    }
}

