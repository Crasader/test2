<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150609162145 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE maintain CHANGE operator operator VARCHAR(30) NOT NULL");
        $this->addSql("ALTER TABLE user_email CHANGE operator operator VARCHAR(30) NOT NULL");
        $this->addSql("ALTER TABLE ip_blacklist CHANGE operator operator VARCHAR(30) NOT NULL");
        $this->addSql("ALTER TABLE petition CHANGE operator operator VARCHAR(30) NOT NULL");
        $this->addSql("ALTER TABLE card_entry CHANGE operator operator VARCHAR(30) DEFAULT '' NOT NULL");
        $this->addSql("ALTER TABLE margin_entry CHANGE operator operator VARCHAR(30) DEFAULT '' NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE card_entry CHANGE operator operator VARCHAR(20) DEFAULT '' NOT NULL");
        $this->addSql("ALTER TABLE ip_blacklist CHANGE operator operator VARCHAR(20) NOT NULL");
        $this->addSql("ALTER TABLE maintain CHANGE operator operator VARCHAR(20) NOT NULL");
        $this->addSql("ALTER TABLE margin_entry CHANGE operator operator VARCHAR(20) DEFAULT '' NOT NULL");
        $this->addSql("ALTER TABLE petition CHANGE operator operator VARCHAR(20) NOT NULL");
        $this->addSql("ALTER TABLE user_email CHANGE operator operator VARCHAR(20) NOT NULL");
    }
}
