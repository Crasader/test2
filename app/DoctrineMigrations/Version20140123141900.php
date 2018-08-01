<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140123141900 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE cash MODIFY version INT DEFAULT 1 NOT NULL AFTER pre_add");
        $this->addSql("ALTER TABLE cash_fake MODIFY version INT DEFAULT 1 NOT NULL AFTER pre_add");
        $this->addSql("ALTER TABLE credit MODIFY version INT DEFAULT 1 NOT NULL AFTER enable");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE cash MODIFY version INT DEFAULT 1 NOT NULL AFTER user_id");
        $this->addSql("ALTER TABLE cash_fake MODIFY version INT DEFAULT 1 NOT NULL AFTER user_id");
        $this->addSql("ALTER TABLE credit MODIFY version INT DEFAULT 1 NOT NULL AFTER group_num");
    }
}
