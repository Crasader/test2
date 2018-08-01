<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130822142947 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE cash_entry CHANGE ref_id ref_id BIGINT DEFAULT 0 NOT NULL");
        $this->addSql("ALTER TABLE cash_fake_entry CHANGE ref_id ref_id BIGINT DEFAULT 0 NOT NULL");
        $this->addSql("ALTER TABLE cash_trans CHANGE ref_id ref_id BIGINT DEFAULT 0 NOT NULL");
        $this->addSql("ALTER TABLE card_entry CHANGE ref_id ref_id BIGINT DEFAULT 0 NOT NULL");
        $this->addSql("ALTER TABLE credit_entry CHANGE ref_id ref_id BIGINT DEFAULT 0 NOT NULL");
        $this->addSql("ALTER TABLE cash_fake_transfer_entry CHANGE ref_id ref_id BIGINT DEFAULT 0 NOT NULL");
        $this->addSql("ALTER TABLE cash_transfer_entry CHANGE ref_id ref_id BIGINT DEFAULT 0 NOT NULL");
        $this->addSql("ALTER TABLE cash_fake_trans CHANGE ref_id ref_id BIGINT DEFAULT 0 NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE cash_entry CHANGE ref_id ref_id BIGINT NOT NULL");
        $this->addSql("ALTER TABLE cash_fake_entry CHANGE ref_id ref_id BIGINT NOT NULL");
        $this->addSql("ALTER TABLE cash_trans CHANGE ref_id ref_id BIGINT NOT NULL");
        $this->addSql("ALTER TABLE card_entry CHANGE ref_id ref_id BIGINT NOT NULL");
        $this->addSql("ALTER TABLE credit_entry CHANGE ref_id ref_id BIGINT NOT NULL");
        $this->addSql("ALTER TABLE cash_fake_transfer_entry CHANGE ref_id ref_id BIGINT NOT NULL");
        $this->addSql("ALTER TABLE cash_transfer_entry CHANGE ref_id ref_id BIGINT NOT NULL");
        $this->addSql("ALTER TABLE cash_fake_trans CHANGE ref_id ref_id BIGINT NOT NULL");
    }
}
