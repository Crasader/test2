<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131112163758 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE maintain_rule CHANGE maintain_at update_at datetime NOT NULL");
        $this->addSql("ALTER TABLE maintain_record CHANGE maintainAt update_at datetime NOT NULL");
        $this->addSql("ALTER TABLE maintain_rule RENAME share_update_cron");
        $this->addSql("ALTER TABLE maintain_record RENAME share_update_record");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE share_update_cron CHANGE update_at maintain_at datetime NOT NULL");
        $this->addSql("ALTER TABLE share_update_record CHANGE update_at maintainAt datetime NOT NULL");
        $this->addSql("ALTER TABLE share_update_cron RENAME maintain_rule");
        $this->addSql("ALTER TABLE share_update_record RENAME maintain_record");
    }
}
