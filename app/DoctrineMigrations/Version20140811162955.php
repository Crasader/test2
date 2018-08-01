<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140811162955 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE deposit_company ADD deposit_sc_max NUMERIC(16, 4) NOT NULL, ADD deposit_sc_min NUMERIC(16, 4) NOT NULL, ADD deposit_co_max NUMERIC(16, 4) NOT NULL, ADD deposit_co_min NUMERIC(16, 4) NOT NULL, ADD deposit_sa_max NUMERIC(16, 4) NOT NULL, ADD deposit_sa_min NUMERIC(16, 4) NOT NULL, ADD deposit_ag_max NUMERIC(16, 4) NOT NULL, ADD deposit_ag_min NUMERIC(16, 4) NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE deposit_company DROP deposit_sc_max, DROP deposit_sc_min, DROP deposit_co_max, DROP deposit_co_min, DROP deposit_sa_max, DROP deposit_sa_min, DROP deposit_ag_max, DROP deposit_ag_min");
    }
}
