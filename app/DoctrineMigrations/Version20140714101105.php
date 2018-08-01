<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140714101105 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE point_error (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, balance NUMERIC(16, 4) NOT NULL, total_amount NUMERIC(16, 4) NOT NULL, at DATETIME NOT NULL, PRIMARY KEY(id))");
        $this->addSql("INSERT INTO `background_process` (`name`, `enable`, `begin_at`, `end_at`, `memo`, `num`, `msg_num`) VALUES ('check-point-error', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00', '檢查成就點數明細, 每天07:05', 0, 0)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE point_error");
        $this->addSql("DELETE FROM background_process WHERE name = 'check-point-error'");
    }
}
