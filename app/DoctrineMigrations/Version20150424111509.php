<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150424111509 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE merchant SET domain = 333333 WHERE domain = 333 AND payway = 3");
        $this->addSql("UPDATE merchant_record SET domain = 333333 WHERE domain = 333");
        $this->addSql("UPDATE merchant_stat SET domain = 333333 WHERE domain = 333");
        $this->addSql("UPDATE payment_charge SET domain = 333333 WHERE domain = 333 AND payway = 3");
        $this->addSql("UPDATE payment_level SET domain = 333333 WHERE domain = 333");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("UPDATE merchant SET domain = 333 WHERE domain = 333333 AND payway = 3");
        $this->addSql("UPDATE merchant_record SET domain = 333 WHERE domain = 333333");
        $this->addSql("UPDATE merchant_stat SET domain = 333 WHERE domain = 333333");
        $this->addSql("UPDATE payment_charge SET domain = 333 WHERE domain = 333333 AND payway = 3");
        $this->addSql("UPDATE payment_level SET domain = 333 WHERE domain = 333333");
    }
}
