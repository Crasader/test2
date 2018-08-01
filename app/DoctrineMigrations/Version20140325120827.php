<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140325120827 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX IDX_E4381A343D7A0C28 ON cash_transfer_entry");
        $this->addSql("DROP INDEX idx_cash_transfer_entry_opcode ON cash_transfer_entry");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE INDEX IDX_E4381A343D7A0C28 ON cash_transfer_entry (cash_id)");
        $this->addSql("CREATE INDEX idx_cash_transfer_entry_opcode ON cash_transfer_entry (opcode)");
    }
}
