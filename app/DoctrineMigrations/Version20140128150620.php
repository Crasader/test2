<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140128150620 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE stat_cash_user (id BIGINT AUTO_INCREMENT NOT NULL, at DATETIME NOT NULL, user_id INT NOT NULL, domain INT NOT NULL, deposit_amount NUMERIC(16, 4) NOT NULL, deposit_count INT UNSIGNED NOT NULL, withdraw_amount NUMERIC(16, 4) NOT NULL, withdraw_count INT UNSIGNED NOT NULL, offer_amount NUMERIC(16, 4) NOT NULL, offer_count INT UNSIGNED NOT NULL, INDEX idx_stat_cash_user_at (at), PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE stat_cash_domain (id BIGINT AUTO_INCREMENT NOT NULL, at DATETIME NOT NULL, domain INT NOT NULL, deposit_amount NUMERIC(16, 4) NOT NULL, deposit_count INT UNSIGNED NOT NULL, withdraw_amount NUMERIC(16, 4) NOT NULL, withdraw_count INT UNSIGNED NOT NULL, offer_amount NUMERIC(16, 4) NOT NULL, offer_count INT UNSIGNED NOT NULL, INDEX idx_stat_cash_domain_at (at), PRIMARY KEY(id))");
        $this->addSql("CREATE UNIQUE INDEX uni_at_user_id ON stat_cash_user (at, user_id)");
        $this->addSql("CREATE UNIQUE INDEX uni_at_domain ON stat_cash_domain (at, domain)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE stat_cash_user");
        $this->addSql("DROP TABLE stat_cash_domain");
        $this->addSql("DROP INDEX uni_at_domain ON stat_cash_domain");
        $this->addSql("DROP INDEX uni_at_user_id ON stat_cash_user");
    }
}
