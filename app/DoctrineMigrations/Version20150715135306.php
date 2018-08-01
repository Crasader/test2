<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150715135306 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO background_process VALUES ('send-immediate-message', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL, '傳送即時訊息, 20秒', 0, 0)");
        $this->addSql("UPDATE background_process SET memo = '傳送訊息, 1分鐘' WHERE name = 'send-message'");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM background_process WHERE name = 'send-immediate-message'");
        $this->addSql("UPDATE background_process SET memo = '傳送訊息' WHERE name = 'send-message'");
    }
}
