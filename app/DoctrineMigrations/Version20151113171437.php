<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151113171437 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE remove_user_list DROP ab_balance, DROP ag_balance, DROP sabah_balance, DROP mg_balance, DROP og_balance");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE remove_user_list ADD ab_balance NUMERIC(16, 4) DEFAULT NULL, ADD ag_balance NUMERIC(16, 4) DEFAULT NULL, ADD sabah_balance NUMERIC(16, 4) DEFAULT NULL, ADD mg_balance NUMERIC(16, 4) DEFAULT NULL, ADD og_balance NUMERIC(16, 4) DEFAULT NULL");
    }
}
