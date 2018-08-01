<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150415142248 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE user_stat (user_id INT NOT NULL, deposit_count INT UNSIGNED NOT NULL, deposit_total NUMERIC(16, 4) NOT NULL, deposit_max NUMERIC(16, 4) NOT NULL, remit_count INT UNSIGNED NOT NULL, remit_total NUMERIC(16, 4) NOT NULL, remit_max NUMERIC(16, 4) NOT NULL, manual_count INT UNSIGNED NOT NULL, manual_total NUMERIC(16, 4) NOT NULL, manual_max NUMERIC(16, 4) NOT NULL, withdraw_count INT UNSIGNED NOT NULL, withdraw_total NUMERIC(16, 4) NOT NULL, withdraw_max NUMERIC(16, 4) NOT NULL, version INT DEFAULT 1 NOT NULL, PRIMARY KEY(user_id))");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE user_stat");
    }
}
