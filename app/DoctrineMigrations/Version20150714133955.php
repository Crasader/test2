<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150714133955 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE level_currency (level_id INT UNSIGNED NOT NULL, currency SMALLINT UNSIGNED NOT NULL, payment_charge_id INT UNSIGNED DEFAULT NULL, user_count INT UNSIGNED NOT NULL, version INT UNSIGNED DEFAULT 1 NOT NULL, PRIMARY KEY(level_id, currency))");
        $this->addSql("ALTER TABLE level_currency ADD CONSTRAINT FK_2EF5538D1601663C FOREIGN KEY (payment_charge_id) REFERENCES payment_charge (id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE level_currency DROP FOREIGN KEY FK_2EF5538D1601663C");
        $this->addSql("DROP TABLE level_currency");
    }
}
