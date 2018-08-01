<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140303163647 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE credit MODIFY group_num INT(11) NOT NULL AFTER user_id");
        $this->addSql("ALTER TABLE credit MODIFY enable TINYINT(1) NOT NULL AFTER group_num");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE credit MODIFY group_num INT(11) NOT NULL AFTER total_line");
        $this->addSql("ALTER TABLE credit MODIFY enable TINYINT(1) NOT NULL AFTER group_num");
    }
}
