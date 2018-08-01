<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130705105727 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        $this->addSql("CREATE TABLE cash_fake_entry_diff (id BIGINT NOT NULL, check_time DATETIME NOT NULL, PRIMARY KEY(id))");
        $this->addSql("INSERT INTO background_process (name, enable, begin_at, end_at, memo, num, msg_num) VALUES ('check_cash_fake_entry', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '檢查快開額度交易明細資料, 1/hour', '0', '0')");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        $this->addSql("DROP TABLE cash_fake_entry_diff");
    }
}
