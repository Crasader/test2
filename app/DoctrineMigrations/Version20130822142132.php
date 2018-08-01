<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130822142132 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE cash_fake_turn_error");
        $this->addSql("DROP TABLE cash_turn_error");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE cash_fake_turn_error (id INT AUTO_INCREMENT NOT NULL, cash_fake_id INT NOT NULL, error_id INT NOT NULL, entry_at BIGINT NOT NULL, PRIMARY KEY(id)) ENGINE = InnoDB;");
        $this->addSql("CREATE TABLE cash_turn_error (id INT AUTO_INCREMENT NOT NULL, cash_id INT NOT NULL, error_id INT NOT NULL, entry_at BIGINT NOT NULL, PRIMARY KEY(id)) ENGINE = InnoDB;");
    }
}
