<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150714144215 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE removed_user_email (user_id INT NOT NULL, email VARCHAR(254) DEFAULT NULL, created_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, confirm TINYINT(1) NOT NULL, confirm_at DATETIME DEFAULT NULL, operator VARCHAR(30) NOT NULL, PRIMARY KEY(user_id))");
        $this->addSql("ALTER TABLE removed_user_email ADD CONSTRAINT FK_2DE8C2D5A76ED395 FOREIGN KEY (user_id) REFERENCES removed_user (user_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE removed_user_email");
    }
}
