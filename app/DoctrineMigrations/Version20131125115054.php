<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131125115054 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE deposit_stat DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE deposit_stat ADD id INT UNSIGNED AUTO_INCREMENT NOT NULL FIRST, ADD month INT UNSIGNED NOT NULL AFTER user_id, ADD PRIMARY KEY (id)");
        $this->addSql("CREATE UNIQUE INDEX uni_deposit_stat ON deposit_stat (user_id, month)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX uni_deposit_stat ON deposit_stat");
        $this->addSql("ALTER TABLE deposit_stat DROP PRIMARY KEY, DROP id, DROP month");
        $this->addSql("ALTER TABLE deposit_stat ADD PRIMARY KEY (user_id)");
    }
}
