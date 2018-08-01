<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150112160204 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX idx_cash_deposit_entry_at_domain ON cash_deposit_entry");
        $this->addSql("CREATE INDEX idx_cash_deposit_entry_at ON cash_deposit_entry (at)");
        $this->addSql("CREATE INDEX idx_cash_deposit_entry_domain_at ON cash_deposit_entry (domain, at)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX idx_cash_deposit_entry_at ON cash_deposit_entry");
        $this->addSql("DROP INDEX idx_cash_deposit_entry_domain_at ON cash_deposit_entry");
        $this->addSql("CREATE INDEX idx_cash_deposit_entry_at_domain ON cash_deposit_entry (at, domain)");
    }
}
