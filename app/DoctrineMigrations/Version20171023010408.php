<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171023010408 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE bitcoin_deposit_entry (id INT UNSIGNED AUTO_INCREMENT NOT NULL, bitcoin_wallet_id INT UNSIGNED NOT NULL, bitcoin_address_id INT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, domain BIGINT UNSIGNED NOT NULL, level_id INT UNSIGNED NOT NULL, at BIGINT UNSIGNED NOT NULL, confirm_at DATETIME DEFAULT NULL, confirm TINYINT(1) NOT NULL, cancel TINYINT(1) NOT NULL, locked TINYINT(1) NOT NULL, amount_entry_id BIGINT NOT NULL, currency SMALLINT UNSIGNED NOT NULL, amount NUMERIC(16, 4) NOT NULL, bitcoin_amount NUMERIC(16, 8) NOT NULL, rate NUMERIC(16, 8) NOT NULL, bitcoin_rate NUMERIC(16, 8) NOT NULL, rate_difference NUMERIC(16, 8) NOT NULL, operator VARCHAR(30) NOT NULL, memo VARCHAR(500) NOT NULL, version INT DEFAULT 1 NOT NULL, PRIMARY KEY(id))');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE bitcoin_deposit_entry');
    }
}
