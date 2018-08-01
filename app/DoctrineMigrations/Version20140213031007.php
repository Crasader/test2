<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140213031007 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX idx_cash_withdraw_entry_status ON cash_withdraw_entry");
        $this->addSql("DROP INDEX idx_cash_withdraw_entry_memo ON cash_withdraw_entry");
        $this->addSql("DROP INDEX idx_cash_withdraw_entry_domain ON cash_withdraw_entry");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE INDEX idx_cash_withdraw_entry_status ON cash_withdraw_entry (status)");
        $this->addSql("CREATE INDEX idx_cash_withdraw_entry_memo ON cash_withdraw_entry (memo)");
        $this->addSql("CREATE INDEX idx_cash_withdraw_entry_domain ON cash_withdraw_entry (domain)");
    }
}
