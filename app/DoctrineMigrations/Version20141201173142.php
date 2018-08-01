<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141201173142 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE INDEX idx_coin_entry_created_at_coin_version ON coin_entry (created_at, coin_version)");
        $this->addSql("CREATE INDEX idx_cash_fake_entry_at_cash_fake_version ON cash_fake_entry (at, cash_fake_version)");
        $this->addSql("CREATE INDEX idx_point_entry_created_at_point_version ON point_entry (created_at, point_version)");
        $this->addSql("CREATE INDEX idx_cash_entry_at_cash_version ON cash_entry (at, cash_version)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX idx_cash_entry_at_cash_version ON cash_entry");
        $this->addSql("DROP INDEX idx_cash_fake_entry_at_cash_fake_version ON cash_fake_entry");
        $this->addSql("DROP INDEX idx_coin_entry_created_at_coin_version ON coin_entry");
        $this->addSql("DROP INDEX idx_point_entry_created_at_point_version ON point_entry");
    }
}
