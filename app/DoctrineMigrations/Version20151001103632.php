<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151001103632 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE INDEX idx_cash_deposit_entry_domain_level ON cash_deposit_entry (domain, level)");
        $this->addSql("CREATE INDEX idx_cash_withdraw_entry_domain_level ON cash_withdraw_entry (domain, level)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX idx_cash_deposit_entry_domain_level ON cash_deposit_entry");
        $this->addSql("DROP INDEX idx_cash_withdraw_entry_domain_level ON cash_withdraw_entry");
    }
}
