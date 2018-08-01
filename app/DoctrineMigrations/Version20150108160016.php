<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150108160016 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX idx_cash_withdraw_entry_created_at ON cash_withdraw_entry");
        $this->addSql("DROP INDEX idx_cash_withdraw_entry_domain_created_at ON cash_withdraw_entry");
        $this->addSql("DROP INDEX idx_cash_withdraw_entry_user_id_created_at ON cash_withdraw_entry");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE INDEX idx_cash_withdraw_entry_created_at ON cash_withdraw_entry (created_at)");
        $this->addSql("CREATE INDEX idx_cash_withdraw_entry_domain_created_at ON cash_withdraw_entry (domain, created_at)");
        $this->addSql("CREATE INDEX idx_cash_withdraw_entry_user_id_created_at ON cash_withdraw_entry (user_id, created_at)");
    }
}
