<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150715111502 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE level_url (id INT UNSIGNED AUTO_INCREMENT NOT NULL, level_id INT UNSIGNED NOT NULL, url VARCHAR(255) NOT NULL, enable TINYINT(1) NOT NULL, INDEX IDX_D74FAA3D5FB14BA7 (level_id), PRIMARY KEY(id))");
        $this->addSql("ALTER TABLE level_url ADD CONSTRAINT FK_D74FAA3D5FB14BA7 FOREIGN KEY (level_id) REFERENCES level (id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE level_url");
    }
}
