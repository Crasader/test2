<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150421180101 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE remove_user_list (id INT UNSIGNED AUTO_INCREMENT NOT NULL, plan_id INT NOT NULL, user_id INT NOT NULL, username VARCHAR(30) NOT NULL, alias VARCHAR(50) NOT NULL, modified_at DATETIME DEFAULT NULL, remove TINYINT(1) NOT NULL, cancel TINYINT(1) NOT NULL, recover_fail TINYINT(1) NOT NULL, get_balance_fail TINYINT(1) NOT NULL, cash_balance NUMERIC(16, 4) DEFAULT NULL, cash_currency SMALLINT UNSIGNED DEFAULT NULL, cash_fake_balance NUMERIC(16, 4) DEFAULT NULL, cash_fake_currency SMALLINT UNSIGNED DEFAULT NULL, credit_line BIGINT DEFAULT NULL, ab_balance NUMERIC(16, 4) DEFAULT NULL, ag_balance NUMERIC(16, 4) DEFAULT NULL, sabah_balance NUMERIC(16, 4) DEFAULT NULL, error_code INT UNSIGNED DEFAULT NULL, memo VARCHAR(100) DEFAULT '' NOT NULL, version INT DEFAULT 1 NOT NULL, PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE remove_user_plan (id INT UNSIGNED AUTO_INCREMENT NOT NULL, creator VARCHAR(30) NOT NULL, parent_id INT NOT NULL, depth SMALLINT DEFAULT NULL, last_login DATETIME NOT NULL, created_at DATETIME NOT NULL, modified_at DATETIME DEFAULT NULL, untreated TINYINT(1) NOT NULL, list_created TINYINT(1) NOT NULL, confirm TINYINT(1) NOT NULL, cancel TINYINT(1) NOT NULL, finished TINYINT(1) NOT NULL, title VARCHAR(20) NOT NULL, memo VARCHAR(100) DEFAULT '' NOT NULL, version INT DEFAULT 1 NOT NULL, PRIMARY KEY(id))");
        $this->addSql("INSERT INTO background_process (name, enable, begin_at, end_at, last_success, memo, num, msg_num) VALUES ('remove-user', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '刪除使用者', '0', '0')");
        $this->addSql("INSERT INTO background_process (name, enable, begin_at, end_at, last_success, memo, num, msg_num) VALUES ('create-remove-user-list', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '新增刪除使用者名單', '0', '0')");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE remove_user_list");
        $this->addSql("DROP TABLE remove_user_plan");
        $this->addSql("DELETE FROM background_process WHERE name = 'remove-user'");
        $this->addSql("DELETE FROM background_process WHERE name = 'create-remove-user-list'");
    }
}
