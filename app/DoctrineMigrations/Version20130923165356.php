<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130923165356 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE cash_withdraw_entry ADD previous_id BIGINT NOT NULL AFTER entry_id, ADD detail_modified TINYINT(1) NOT NULL AFTER first");
        $this->addSql("ALTER TABLE acc_param ADD previous_id BIGINT NOT NULL AFTER from_id, ADD detail_modified TINYINT(1) NOT NULL AFTER is_test");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE cash_withdraw_entry DROP previous_id, DROP detail_modified");
        $this->addSql("ALTER TABLE acc_param DROP previous_id, DROP detail_modified");
    }
}
