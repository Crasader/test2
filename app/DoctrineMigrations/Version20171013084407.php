<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171013084407 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_stat ADD bitcoin_deposit_count INT UNSIGNED NOT NULL AFTER manual_max, ADD bitcoin_deposit_total NUMERIC(16, 4) NOT NULL AFTER bitcoin_deposit_count, ADD bitcoin_deposit_max NUMERIC(16, 4) NOT NULL AFTER bitcoin_deposit_total, ADD bitcoin_withdraw_count INT UNSIGNED NOT NULL AFTER last_withdraw_at, ADD bitcoin_withdraw_total NUMERIC(16, 4) NOT NULL AFTER bitcoin_withdraw_count, ADD bitcoin_withdraw_max NUMERIC(16, 4) NOT NULL AFTER bitcoin_withdraw_total, ADD last_bitcoin_withdraw_address VARCHAR(64) NOT NULL AFTER bitcoin_withdraw_max, ADD last_bitcoin_withdraw_at BIGINT UNSIGNED DEFAULT 0 NOT NULL AFTER last_bitcoin_withdraw_address');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_stat DROP bitcoin_deposit_count, DROP bitcoin_deposit_total, DROP bitcoin_deposit_max, DROP bitcoin_withdraw_count, DROP bitcoin_withdraw_total, DROP bitcoin_withdraw_max, DROP last_bitcoin_withdraw_address, DROP last_bitcoin_withdraw_at');
    }
}
