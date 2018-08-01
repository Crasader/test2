<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150720091911 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE remit_account_level ADD new_level INT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE remit_entry ADD level_id INT UNSIGNED NOT NULL AFTER user_level, CHANGE user_level user_level SMALLINT UNSIGNED DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE remit_account_level DROP new_level");
        $this->addSql("ALTER TABLE remit_entry DROP level_id, CHANGE user_level user_level SMALLINT UNSIGNED NOT NULL");
    }
}
