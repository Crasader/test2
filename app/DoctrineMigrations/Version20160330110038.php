<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160330110038 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE remit_entry_old');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE remit_entry_old (id INT UNSIGNED AUTO_INCREMENT NOT NULL, created_at BIGINT UNSIGNED NOT NULL, remit_account_id SMALLINT UNSIGNED NOT NULL, order_number BIGINT NOT NULL, old_order_number VARCHAR(20) NOT NULL, user_id INT NOT NULL, username VARCHAR(30) NOT NULL, user_level SMALLINT UNSIGNED DEFAULT NULL, level_id INT UNSIGNED NOT NULL, ancestor_id INT NOT NULL, bank_info_id INT NOT NULL, name_real VARCHAR(32) NOT NULL, method SMALLINT UNSIGNED NOT NULL, branch VARCHAR(64) DEFAULT NULL, rate NUMERIC(16, 8) NOT NULL, amount NUMERIC(16, 4) NOT NULL, amount_entry_id BIGINT NOT NULL, discount NUMERIC(16, 4) NOT NULL, discount_entry_id BIGINT NOT NULL, other_discount NUMERIC(16, 4) NOT NULL, actual_other_discount NUMERIC(16, 4) NOT NULL, other_discount_entry_id BIGINT NOT NULL, abandon_discount TINYINT(1) NOT NULL, cellphone VARCHAR(20) NOT NULL, trade_number VARCHAR(18) NOT NULL, payer_card VARCHAR(30) NOT NULL, transfer_code VARCHAR(18) NOT NULL, atm_terminal_code VARCHAR(18) NOT NULL, memo VARCHAR(255) NOT NULL, identity_card VARCHAR(18) NOT NULL, status SMALLINT UNSIGNED NOT NULL, operator VARCHAR(30) NOT NULL, deposit_at DATETIME DEFAULT NULL, confirm_at DATETIME DEFAULT NULL, duration INT UNSIGNED NOT NULL, version INT DEFAULT 1 NOT NULL, INDEX idx_remit_entry_confirm_at (confirm_at), INDEX idx_remit_entry_created_at (created_at), INDEX idx_remit_entry_order_number (order_number), INDEX idx_remit_entry_remit_account_id_created_at (remit_account_id, created_at), INDEX idx_remit_entry_remit_account_id_confirm_at (remit_account_id, confirm_at), INDEX idx_remit_entry_user_id (user_id), PRIMARY KEY(id))');
    }
}
