<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150423092128 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE blacklist (id INT AUTO_INCREMENT NOT NULL, domain INT DEFAULT NULL, whole_domain TINYINT(1) NOT NULL, account VARCHAR(36) DEFAULT NULL, identity_card VARCHAR(18) DEFAULT NULL, name_real VARCHAR(100) DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, email VARCHAR(50) DEFAULT NULL, ip INT UNSIGNED DEFAULT NULL, created_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, note VARCHAR(150) DEFAULT NULL, removed TINYINT(1) NOT NULL, INDEX idx_blacklist_domain (domain), INDEX idx_blacklist_account (account), INDEX idx_blacklist_identity_card (identity_card), INDEX idx_blacklist_name_real (name_real), INDEX idx_blacklist_telephone (telephone), INDEX idx_blacklist_email (email), INDEX idx_blacklist_ip (ip), PRIMARY KEY(id))");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE blacklist");
    }
}
