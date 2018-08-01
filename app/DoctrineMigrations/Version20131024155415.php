<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20131024155415 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE acc_param RENAME TO account_log, DROP INDEX idx_acc_param_status, DROP INDEX idx_acc_param_count, ADD INDEX idx_account_log_status(status), ADD INDEX idx_account_log_count(count)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE account_log RENAME TO acc_param, DROP INDEX idx_account_log_status, DROP INDEX idx_account_log_count, ADD INDEX idx_acc_param_status(status), ADD INDEX idx_acc_param_count(count)");
    }
}
