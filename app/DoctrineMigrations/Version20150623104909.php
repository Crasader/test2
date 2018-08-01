<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150623104909 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE payment_gateway SET verify_ip = '172.26.54.3' WHERE id IN (6, 31, 34, 38, 65, 75, 78, 79, 80, 81, 82, 84, 87, 88, 89, 90, 93)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE payment_gateway SET verify_ip = '172.26.54.2' WHERE id IN (6, 31, 34, 38, 65, 75, 78, 79, 80, 81, 82, 84, 87, 88, 89, 90, 93)");
    }
}
