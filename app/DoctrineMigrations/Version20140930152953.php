<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140930152953 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE domain_config (domain INT NOT NULL, block_create_user TINYINT(1) NOT NULL, PRIMARY KEY(domain))");
        $this->addSql("CREATE TABLE ip_blacklist (id INT AUTO_INCREMENT NOT NULL, domain INT NOT NULL, ip INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, removed TINYINT(1) NOT NULL, operator VARCHAR(20) NOT NULL, PRIMARY KEY(id))");
        }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE domain_config");
        $this->addSql("DROP TABLE ip_blacklist");
    }
}
