<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140410114133 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE stat_cash_domain");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE stat_cash_domain (id BIGINT AUTO_INCREMENT NOT NULL, at DATETIME NOT NULL, domain INT NOT NULL, deposit_amount NUMERIC(16, 4) NOT NULL, deposit_count INT UNSIGNED NOT NULL, withdraw_amount NUMERIC(16, 4) NOT NULL, withdraw_count INT UNSIGNED NOT NULL, offer_amount NUMERIC(16, 4) NOT NULL, offer_count INT UNSIGNED NOT NULL, UNIQUE INDEX uni_at_domain (at, domain), INDEX idx_stat_cash_domain_at (at), PRIMARY KEY(id))");
    }
}
