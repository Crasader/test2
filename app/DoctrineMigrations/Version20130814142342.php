<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130814142342 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO exchange(id, currency, buy, basic, sell, active_at) VALUES (NULL, 'DGC', '0.01', '0.01', '0.01', '2013-08-14 12:00:00')");
        $this->addSql("CREATE TABLE domain_currency (domain INT NOT NULL, currency SMALLINT UNSIGNED NOT NULL, preset TINYINT(1) NOT NULL, PRIMARY KEY(domain, currency))");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM exchange_record WHERE currency = 'DGC'");
        $this->addSql("DELETE FROM exchange WHERE currency = 'DGC'");
        $this->addSql("DROP TABLE domain_currency");
    }
}
