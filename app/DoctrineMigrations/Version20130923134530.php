<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130923134530 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        $this->addSql("CREATE TABLE remit_account (id SMALLINT UNSIGNED AUTO_INCREMENT NOT NULL, domain INT NOT NULL, bank_info_id INT NOT NULL, account_type SMALLINT UNSIGNED NOT NULL, account VARCHAR(40) NOT NULL, currency SMALLINT UNSIGNED NOT NULL, control_tips VARCHAR(100) NOT NULL, recipient VARCHAR(100) NOT NULL, branch VARCHAR(100) NOT NULL, user_tips VARCHAR(128) NOT NULL, enable TINYINT(1) NOT NULL, deleted TINYINT(1) NOT NULL, PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE remit_account_level (remit_account_id SMALLINT UNSIGNED NOT NULL, level_id SMALLINT UNSIGNED NOT NULL, PRIMARY KEY(remit_account_id, level_id))");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        $this->addSql("DROP TABLE remit_account_level");
        $this->addSql("DROP TABLE remit_account");
    }
}
