<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140312125314 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE petition (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, old_value VARCHAR(100) NOT NULL, value VARCHAR(100) NOT NULL, operator VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, active_at DATETIME DEFAULT NULL, untreated TINYINT(1) NOT NULL, confirm TINYINT(1) NOT NULL, cancel TINYINT(1) NOT NULL, version INT DEFAULT 1 NOT NULL, PRIMARY KEY(id))");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE petition");
    }
}
