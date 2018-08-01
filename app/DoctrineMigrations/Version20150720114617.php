<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150720114617 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE preset_level (user_id INT NOT NULL, level_id INT UNSIGNED NOT NULL, INDEX IDX_260D47465FB14BA7 (level_id), PRIMARY KEY(user_id))");
        $this->addSql("ALTER TABLE preset_level ADD CONSTRAINT FK_260D4746A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)");
        $this->addSql("ALTER TABLE preset_level ADD CONSTRAINT FK_260D47465FB14BA7 FOREIGN KEY (level_id) REFERENCES level (id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE preset_level");
    }
}
