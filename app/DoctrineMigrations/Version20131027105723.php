<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131027105723 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE transcribe_entry (id INT UNSIGNED AUTO_INCREMENT NOT NULL, remit_account_id SMALLINT UNSIGNED NOT NULL, amount NUMERIC(16, 4) NOT NULL, fee NUMERIC(16, 4) NOT NULL, method SMALLINT UNSIGNED NOT NULL, name_real VARCHAR(32) NOT NULL, location VARCHAR(32) NOT NULL, blank TINYINT(1) NOT NULL, confirm TINYINT(1) NOT NULL, withdraw TINYINT(1) NOT NULL, deleted TINYINT(1) NOT NULL, creator VARCHAR(20) DEFAULT NULL, booked_at DATETIME NOT NULL, first_transcribe_at DATETIME DEFAULT NULL, transcribe_at DATETIME DEFAULT NULL, recipient_account_id SMALLINT UNSIGNED DEFAULT NULL, memo VARCHAR(100) DEFAULT '' NOT NULL, trade_memo VARCHAR(100) DEFAULT '' NOT NULL, rank SMALLINT NOT NULL, remit_entry_id INT UNSIGNED DEFAULT NULL, force_confirm TINYINT(1) DEFAULT '0' NOT NULL, force_operator VARCHAR(20) DEFAULT NULL, force_confirm_at DATETIME DEFAULT NULL, update_at DATETIME NOT NULL, version INT DEFAULT 1 NOT NULL, UNIQUE INDEX uni_transcribe_entry_remit_account_id_rank (remit_account_id, rank), INDEX idx_transcribe_entry_booked_at (booked_at), INDEX idx_transcribe_entry_first_transcribe_at (first_transcribe_at), INDEX idx_transcribe_entry_force_confirm_at (force_confirm_at), PRIMARY KEY(id))");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE transcribe_entry");
    }
}
