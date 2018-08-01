<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140324163743 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX IDX_84518F4C9354354D ON cash_fake_entry");
        $this->addSql("DROP INDEX idx_cash_fake_entry_opcode ON cash_fake_entry");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE INDEX IDX_84518F4C9354354D ON cash_fake_entry (cash_fake_id)");
        $this->addSql("CREATE INDEX idx_cash_fake_entry_opcode ON cash_fake_entry (opcode)");
    }
}
