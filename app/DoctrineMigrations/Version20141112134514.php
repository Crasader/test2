<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141112134514 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE login_log ADD ipv6 VARCHAR(40) NOT NULL AFTER ip, ADD host VARCHAR(255) NOT NULL AFTER ipv6, ADD username VARCHAR(20) NOT NULL AFTER user_id, ADD role SMALLINT DEFAULT NULL AFTER username, ADD lang VARCHAR(20) NOT NULL, ADD os VARCHAR(8) NOT NULL, CHANGE ip ip INT NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE login_log DROP ipv6, DROP role, DROP host, DROP username, DROP lang, DROP os, CHANGE ip ip VARCHAR(25) NOT NULL");
    }
}
