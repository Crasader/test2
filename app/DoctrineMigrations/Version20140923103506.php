<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140923103506 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE margin_entry (id BIGINT NOT NULL, margin_id INT NOT NULL, user_id INT NOT NULL, opcode INT NOT NULL, amount NUMERIC(16, 4) NOT NULL, balance NUMERIC(16, 4) NOT NULL, gambler_id INT DEFAULT NULL, operator VARCHAR(20) DEFAULT '' NOT NULL, ref_id BIGINT DEFAULT '0' NOT NULL, created_at DATETIME NOT NULL, margin_version INT UNSIGNED DEFAULT 0 NOT NULL, INDEX IDX_923A07DEEFB9A5B6 (margin_id), INDEX idx_margin_entry_created_at (created_at), INDEX idx_margin_entry_gambler_id (gambler_id), PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE margin (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, enable_num INT NOT NULL, enable TINYINT(1) NOT NULL, balance NUMERIC(16, 4) NOT NULL, last_balance NUMERIC(16, 4) NOT NULL, version INT DEFAULT 1 NOT NULL, INDEX IDX_3361CAD9A76ED395 (user_id), PRIMARY KEY(id))");
        $this->addSql("ALTER TABLE margin_entry ADD CONSTRAINT FK_923A07DEEFB9A5B6 FOREIGN KEY (margin_id) REFERENCES margin (id)");
        $this->addSql("ALTER TABLE margin ADD CONSTRAINT FK_3361CAD9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)");
        $this->addSql("CREATE TABLE margin_error (id INT AUTO_INCREMENT NOT NULL, margin_id INT NOT NULL, user_id INT NOT NULL, balance NUMERIC(16, 4) NOT NULL, total_amount NUMERIC(16, 4) NOT NULL, at DATETIME NOT NULL, PRIMARY KEY(id))");
        $this->addSql("INSERT INTO background_process Values ('run-margin-poper', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '更新保證金明細, 1/sec', 0, 0)");
        $this->addSql("INSERT INTO background_process Values ('run-margin-sync', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '同步保證金餘額, 1/sec', 0, 0)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE margin_entry DROP FOREIGN KEY FK_923A07DEEFB9A5B6");
        $this->addSql("DROP TABLE margin_entry");
        $this->addSql("DROP TABLE margin");
        $this->addSql("DROP TABLE margin_error");
        $this->addSql("DELETE FROM background_process Where name = 'run-margin-poper'");
        $this->addSql("DELETE FROM background_process Where name = 'run-margin-sync'");
    }
}
