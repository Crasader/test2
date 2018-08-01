<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170918015516 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE remit_entry ADD domain INT NOT NULL AFTER remit_account_id');
        $this->addSql('CREATE INDEX idx_remit_entry_domain_created_at ON remit_entry (domain, created_at)');
        $this->addSql('CREATE INDEX idx_remit_entry_domain_confirm_at ON remit_entry (domain, confirm_at)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX idx_remit_entry_domain_confirm_at ON remit_entry');
        $this->addSql('DROP INDEX idx_remit_entry_domain_created_at ON remit_entry');
        $this->addSql('ALTER TABLE remit_entry DROP domain');
    }
}
