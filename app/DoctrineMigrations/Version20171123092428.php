<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171123092428 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE INDEX idx_bitcoin_deposit_entry_user_id ON bitcoin_deposit_entry (user_id)');
        $this->addSql('CREATE INDEX idx_bitcoin_deposit_entry_confirm_at ON bitcoin_deposit_entry (confirm_at)');
        $this->addSql('CREATE INDEX idx_bitcoin_deposit_entry_at ON bitcoin_deposit_entry (at)');
        $this->addSql('CREATE INDEX idx_bitcoin_deposit_entry_domain_at ON bitcoin_deposit_entry (domain, at)');
        $this->addSql('CREATE INDEX idx_bitcoin_withdraw_entry_at ON bitcoin_withdraw_entry (at)');
        $this->addSql('CREATE INDEX idx_bitcoin_withdraw_entry_user_id ON bitcoin_withdraw_entry (user_id)');
        $this->addSql('CREATE INDEX idx_bitcoin_withdraw_entry_confirm_at ON bitcoin_withdraw_entry (confirm_at)');
        $this->addSql('CREATE INDEX idx_bitcoin_withdraw_entry_domain_at ON bitcoin_withdraw_entry (domain, at)');
        $this->addSql('CREATE INDEX idx_bitcoin_withdraw_entry_user_id_at ON bitcoin_withdraw_entry (user_id, at)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX idx_bitcoin_deposit_entry_user_id ON bitcoin_deposit_entry');
        $this->addSql('DROP INDEX idx_bitcoin_deposit_entry_confirm_at ON bitcoin_deposit_entry');
        $this->addSql('DROP INDEX idx_bitcoin_deposit_entry_at ON bitcoin_deposit_entry');
        $this->addSql('DROP INDEX idx_bitcoin_deposit_entry_domain_at ON bitcoin_deposit_entry');
        $this->addSql('DROP INDEX idx_bitcoin_withdraw_entry_at ON bitcoin_withdraw_entry');
        $this->addSql('DROP INDEX idx_bitcoin_withdraw_entry_user_id ON bitcoin_withdraw_entry');
        $this->addSql('DROP INDEX idx_bitcoin_withdraw_entry_confirm_at ON bitcoin_withdraw_entry');
        $this->addSql('DROP INDEX idx_bitcoin_withdraw_entry_domain_at ON bitcoin_withdraw_entry');
        $this->addSql('DROP INDEX idx_bitcoin_withdraw_entry_user_id_at ON bitcoin_withdraw_entry');
    }
}
