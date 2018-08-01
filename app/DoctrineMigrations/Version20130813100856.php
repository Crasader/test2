<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130813100856 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE cash_transfer_entry MODIFY at bigint AFTER id");
        $this->addSql("ALTER TABLE cash_fake_transfer_entry MODIFY at bigint AFTER id");
        $this->addSql("ALTER table cash_transfer_entry DROP PRIMARY KEY, add PRIMARY KEY(id,at)");
        $this->addSql("ALTER table cash_fake_transfer_entry DROP PRIMARY KEY, add PRIMARY KEY(id,at)");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER table cash_transfer_entry DROP PRIMARY KEY, add PRIMARY KEY(id)");
        $this->addSql("ALTER table cash_fake_transfer_entry DROP PRIMARY KEY, add PRIMARY KEY(id)");
        $this->addSql("ALTER TABLE cash_transfer_entry MODIFY at bigint AFTER memo");
        $this->addSql("ALTER TABLE cash_fake_transfer_entry MODIFY at bigint AFTER memo");

    }
}
