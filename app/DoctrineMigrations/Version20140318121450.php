<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140318121450 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        $this->addSql("ALTER TABLE cash_withdraw_entry CHANGE at created_at DATETIME NOT NULL");
        $this->addSql("ALTER TABLE cash_withdraw_entry DROP INDEX idx_cash_withdraw_entry_at");
        $this->addSql("ALTER TABLE cash_withdraw_entry ADD INDEX idx_cash_withdraw_entry_created_at (created_at)");
        $this->addSql("ALTER TABLE cash_withdraw_entry DROP INDEX idx_cash_withdraw_entry_domain_at");
        $this->addSql("ALTER TABLE cash_withdraw_entry ADD INDEX idx_cash_withdraw_entry_domain_created_at (domain, created_at)");
        $this->addSql("ALTER TABLE cash_withdraw_entry DROP INDEX idx_cash_withdraw_entry_user_id_at");
        $this->addSql("ALTER TABLE cash_withdraw_entry ADD INDEX idx_cash_withdraw_entry_user_id_created_at (user_id, created_at)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        $this->addSql("ALTER TABLE cash_withdraw_entry CHANGE created_at at DATETIME NOT NULL");
        $this->addSql("ALTER TABLE cash_withdraw_entry DROP INDEX idx_cash_withdraw_entry_created_at");
        $this->addSql("ALTER TABLE cash_withdraw_entry ADD INDEX idx_cash_withdraw_entry_at (at)");
        $this->addSql("ALTER TABLE cash_withdraw_entry DROP INDEX idx_cash_withdraw_entry_domain_created_at");
        $this->addSql("ALTER TABLE cash_withdraw_entry ADD INDEX idx_cash_withdraw_entry_domain_at (domain, at)");
        $this->addSql("ALTER TABLE cash_withdraw_entry DROP INDEX idx_cash_withdraw_entry_user_id_created_at");
        $this->addSql("ALTER TABLE cash_withdraw_entry ADD INDEX idx_cash_withdraw_entry_user_id_at (user_id, at)");
    }
}
