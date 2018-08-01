<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171024071906 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE bitcoin_withdraw_entry (id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, domain BIGINT UNSIGNED NOT NULL, level_id INT UNSIGNED NOT NULL, at BIGINT UNSIGNED NOT NULL, confirm_at DATETIME DEFAULT NULL, confirm TINYINT(1) NOT NULL, cancel TINYINT(1) NOT NULL, locked TINYINT(1) NOT NULL, manual TINYINT(1) NOT NULL, first TINYINT(1) NOT NULL, detail_modified TINYINT(1) NOT NULL, amount_entry_id BIGINT NOT NULL, previous_id BIGINT NOT NULL, currency SMALLINT UNSIGNED NOT NULL, amount NUMERIC(16, 4) NOT NULL, bitcoin_amount NUMERIC(16, 8) NOT NULL, rate NUMERIC(16, 8) NOT NULL, bitcoin_rate NUMERIC(16, 8) NOT NULL, rate_difference NUMERIC(16, 8) NOT NULL, aduit_fee NUMERIC(16, 4) NOT NULL, aduit_charge NUMERIC(16, 4) NOT NULL, deduction NUMERIC(16, 4) NOT NULL, real_amount NUMERIC(16, 4) NOT NULL, ip INT UNSIGNED NOT NULL, operator VARCHAR(30) NOT NULL, withdraw_address VARCHAR(64) NOT NULL, memo VARCHAR(500) NOT NULL, note VARCHAR(150) DEFAULT \'\' NOT NULL, version INT DEFAULT 1 NOT NULL, PRIMARY KEY(id))');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE bitcoin_withdraw_entry');
    }
}
