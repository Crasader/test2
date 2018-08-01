<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150715085723 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE level (id INT UNSIGNED AUTO_INCREMENT NOT NULL, domain INT NOT NULL, alias VARCHAR(50) NOT NULL, old_level SMALLINT UNSIGNED DEFAULT NULL, order_strategy SMALLINT UNSIGNED NOT NULL, created_at_start DATETIME NOT NULL, created_at_end DATETIME NOT NULL, deposit_count INT UNSIGNED NOT NULL, deposit_total BIGINT UNSIGNED NOT NULL, deposit_max BIGINT UNSIGNED NOT NULL, withdraw_count INT UNSIGNED NOT NULL, withdraw_total BIGINT UNSIGNED NOT NULL, user_count INT UNSIGNED NOT NULL, memo VARCHAR(50) NOT NULL, UNIQUE INDEX uni_level_domain_alias (domain, alias), PRIMARY KEY(id))");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE level");
    }
}
