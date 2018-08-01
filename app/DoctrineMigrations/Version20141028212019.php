<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141028212019 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE coin_entry ADD coin_version INT UNSIGNED DEFAULT 0 NOT NULL");
        $this->addSql("ALTER TABLE cash_fake_entry ADD cash_fake_version INT UNSIGNED DEFAULT 0 NOT NULL");
        $this->addSql("ALTER TABLE card_entry ADD card_version INT UNSIGNED DEFAULT 0 NOT NULL");
        $this->addSql("ALTER TABLE point_entry ADD point_version INT UNSIGNED DEFAULT 0 NOT NULL");
        $this->addSql("ALTER TABLE credit_entry ADD credit_version INT UNSIGNED DEFAULT 0 NOT NULL");
        $this->addSql("ALTER TABLE cash_entry ADD cash_version INT UNSIGNED DEFAULT 0 NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE card_entry DROP card_version");
        $this->addSql("ALTER TABLE cash_entry DROP cash_version");
        $this->addSql("ALTER TABLE cash_fake_entry DROP cash_fake_version");
        $this->addSql("ALTER TABLE coin_entry DROP coin_version");
        $this->addSql("ALTER TABLE credit_entry DROP credit_version");
        $this->addSql("ALTER TABLE point_entry DROP point_version");
    }
}
