<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170609161243 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE auto_confirm_entry (id INT UNSIGNED AUTO_INCREMENT NOT NULL, created_at BIGINT UNSIGNED NOT NULL, confirm_at DATETIME DEFAULT NULL, confirm TINYINT(1) NOT NULL, manual TINYINT(1) NOT NULL, remit_account_id INT UNSIGNED NOT NULL, remit_entry_id INT UNSIGNED NOT NULL, amount NUMERIC(16, 4) NOT NULL, fee NUMERIC(16, 4) NOT NULL, balance NUMERIC(16, 4) NOT NULL, trade_at DATETIME NOT NULL, method VARCHAR(30) NOT NULL, account VARCHAR(30) NOT NULL, name VARCHAR(32) NOT NULL, trade_memo VARCHAR(100) NOT NULL, message VARCHAR(100) NOT NULL, memo VARCHAR(100) NOT NULL, version INT DEFAULT 1 NOT NULL, INDEX idx_auto_confirm_entry_created_at (created_at), INDEX idx_auto_confirm_entry_confirm_at (confirm_at), PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE remit_account ADD balance NUMERIC(16, 4) NOT NULL AFTER bank_info_id, ADD bank_limit NUMERIC(16, 4) NOT NULL AFTER balance, ADD password_error TINYINT(1) NOT NULL AFTER bb_auto_confirm, ADD crawler_on TINYINT(1) NOT NULL AFTER password_error, ADD crawler_update DATETIME DEFAULT NULL AFTER crawler_on, ADD web_bank_account VARCHAR(40) NOT NULL AFTER account, ADD web_bank_password VARCHAR(100) NOT NULL AFTER web_bank_account');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE remit_account DROP balance, DROP bank_limit, DROP password_error, DROP crawler_on, DROP crawler_update, DROP web_bank_account, DROP web_bank_password');
        $this->addSql('DROP TABLE auto_confirm_entry');
    }
}
