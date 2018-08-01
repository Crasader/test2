<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130925112756 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE maintain_status (id SMALLINT UNSIGNED AUTO_INCREMENT NOT NULL, maintain_code SMALLINT UNSIGNED NOT NULL, status SMALLINT UNSIGNED NOT NULL, target VARCHAR(10) NOT NULL, update_at DATETIME NOT NULL, INDEX IDX_6A14A35F6027A2C5 (maintain_code), PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE maintain (code SMALLINT UNSIGNED NOT NULL, begin_at DATETIME NOT NULL, end_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, msg VARCHAR(100) NOT NULL, operator VARCHAR(20) NOT NULL, PRIMARY KEY(code))");
        $this->addSql("ALTER TABLE maintain_status ADD CONSTRAINT FK_6A14A35F6027A2C5 FOREIGN KEY (maintain_code) REFERENCES maintain (code)");
        $this->addSql("INSERT INTO background_process (name, enable, begin_at, end_at, memo, num, msg_num) VALUES ('send_maintain_message', '0', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '發送維護訊息, 1/min', '0', '0')");
        $this->addSql("INSERT INTO `maintain` (`code`, `begin_at`, `end_at`, `modified_at`, `msg`, `operator`) VALUES
        (1, '2000-01-01 00:00:00', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '', ''),
        (2, '2000-01-01 00:00:00', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '', ''),
        (3, '2000-01-01 00:00:00', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '', ''),
        (4, '2000-01-01 00:00:00', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '', ''),
        (5, '2000-01-01 00:00:00', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '', ''),
        (12, '2000-01-01 00:00:00', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '', '')");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE maintain_status DROP FOREIGN KEY FK_6A14A35F6027A2C5");
        $this->addSql("DROP TABLE maintain_status");
        $this->addSql("DROP TABLE maintain");
        $this->addSql("DELETE FROM background_process WHERE name = 'send_maintain_message'");
    }
}
