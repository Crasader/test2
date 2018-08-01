<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141222102951 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE chip_error (id INT AUTO_INCREMENT NOT NULL, chip_id INT NOT NULL, user_id INT NOT NULL, game_code INT NOT NULL, balance INT NOT NULL, total_amount INT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE chip_entry_diff (id BIGINT NOT NULL, check_time DATETIME NOT NULL, PRIMARY KEY(id))");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE chip_error");
        $this->addSql("DROP TABLE chip_entry_diff");
    }
}
