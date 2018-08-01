<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140331153808 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        $this->addSql("ALTER TABLE cash_withdraw_entry CHANGE id id BIGINT NOT NULL");
        $this->addSql("ALTER TABLE cash_withdraw_entry DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE cash_withdraw_entry ADD PRIMARY KEY (id, at)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        $this->addSql("ALTER TABLE cash_withdraw_entry CHANGE id id BIGINT AUTO_INCREMENT NOT NULL");
        $this->addSql("ALTER TABLE cash_withdraw_entry DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE cash_withdraw_entry ADD PRIMARY KEY (id)");
    }
}
