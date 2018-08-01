<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150722140427 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE remit_account_level DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE remit_account_level CHANGE level_id old_level SMALLINT UNSIGNED DEFAULT NULL, CHANGE new_level level_id INT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE remit_account_level ADD PRIMARY KEY (remit_account_id, level_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE remit_account_level DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE remit_account_level CHANGE level_id new_level INT UNSIGNED NOT NULL, CHANGE old_level level_id SMALLINT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE remit_account_level ADD PRIMARY KEY (remit_account_id, level_id)");
    }
}
