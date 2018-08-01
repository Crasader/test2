<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141120101530 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX idx_cash_withdraw_entry_update_at ON cash_withdraw_entry");
        $this->addSql("ALTER TABLE cash_withdraw_entry CHANGE update_at confirm_at DATETIME DEFAULT NULL");
        $this->addSql("CREATE INDEX idx_cash_withdraw_entry_confirm_at ON cash_withdraw_entry (confirm_at)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX idx_cash_withdraw_entry_confirm_at ON cash_withdraw_entry");
        $this->addSql("ALTER TABLE cash_withdraw_entry CHANGE confirm_at update_at DATETIME DEFAULT NULL");
        $this->addSql("CREATE INDEX idx_cash_withdraw_entry_update_at ON cash_withdraw_entry (update_at)");
    }
}
