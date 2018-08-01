<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170708065144 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== "mysql", "Migration can only be executed safely on \"mysql\".");

        $this->addSql("ALTER TABLE cash_withdraw_entry ADD account_holder VARCHAR(100) DEFAULT '' NOT NULL");
        $this->addSql("ALTER TABLE bank ADD account_holder VARCHAR(100) DEFAULT '' NOT NULL");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== "mysql", "Migration can only be executed safely on \"mysql\".");

        $this->addSql("ALTER TABLE bank DROP account_holder");
        $this->addSql("ALTER TABLE cash_withdraw_entry DROP account_holder");
    }
}
