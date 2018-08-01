<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140303164017 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE credit_entry ADD user_id INT NOT NULL AFTER credit_id, ADD group_num INT NOT NULL AFTER user_id");
        $this->addSql("CREATE INDEX idx_credit_entry_user_id_group_num ON credit_entry (user_id, group_num)");
        $this->addSql("ALTER TABLE credit_period ADD user_id INT NOT NULL AFTER credit_id, ADD group_num INT NOT NULL AFTER user_id");

        // 變更資料
        $this->addSql("UPDATE credit_period cp INNER JOIN credit c ON cp.credit_id = c.id SET cp.user_id = c.user_id, cp.group_num = c.group_num");
        $this->addSql("UPDATE credit_entry ce INNER JOIN credit c ON ce.credit_id = c.id SET ce.user_id = c.user_id, ce.group_num = c.group_num");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX idx_credit_entry_user_id_group_num ON credit_entry");
        $this->addSql("ALTER TABLE credit_entry DROP user_id, DROP group_num");
        $this->addSql("ALTER TABLE credit_period DROP user_id, DROP group_num");
    }
}
