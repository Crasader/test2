<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140324112927 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        $this->addSql("ALTER TABLE cash_withdraw_entry ADD at BIGINT UNSIGNED NOT NULL AFTER id");
        $this->addSql("CREATE INDEX idx_cash_withdraw_entry_at ON cash_withdraw_entry (at)");
        $this->addSql("UPDATE cash_withdraw_entry SET at = DATE_FORMAT(created_at, '%Y%m%d%H%i%s')");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        $this->addSql("DROP INDEX idx_cash_withdraw_entry_at ON cash_withdraw_entry");
        $this->addSql("ALTER TABLE cash_withdraw_entry DROP at");
    }
}
