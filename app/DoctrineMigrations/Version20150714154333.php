<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150714154333 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE user_level (user_id INT NOT NULL, locked TINYINT(1) NOT NULL, level_id INT UNSIGNED NOT NULL, last_level_id INT UNSIGNED NOT NULL, INDEX idx_user_level_level_id (level_id), PRIMARY KEY(user_id))");
        $this->addSql("ALTER TABLE user_level ADD CONSTRAINT FK_7828374BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)");
        $this->addSql("DROP TABLE user_payment_level");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE user_payment_level (user_id INT NOT NULL, block TINYINT(1) NOT NULL, payment_level SMALLINT UNSIGNED NOT NULL, last_payment_level SMALLINT UNSIGNED NOT NULL, INDEX idx_user_payment_level (payment_level), PRIMARY KEY(user_id))");
        $this->addSql("ALTER TABLE user_payment_level ADD CONSTRAINT FK_232D57F1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)");
        $this->addSql("DROP TABLE user_level");
    }
}
