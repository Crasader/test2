<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150518160910 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE transcribe_entry CHANGE creator creator VARCHAR(30) DEFAULT NULL, CHANGE username username VARCHAR(30) DEFAULT NULL, CHANGE force_operator force_operator VARCHAR(30) DEFAULT NULL");
        $this->addSql("ALTER TABLE remit_entry CHANGE username username VARCHAR(30) NOT NULL, CHANGE operator operator VARCHAR(30) NOT NULL");
        $this->addSql("ALTER TABLE deposit_suda_entry CHANGE checked_username checked_username VARCHAR(30) NOT NULL");
        $this->addSql("ALTER TABLE cash_withdraw_entry CHANGE checked_username checked_username VARCHAR(30) DEFAULT NULL");
        $this->addSql("ALTER TABLE withdraw_entry_lock CHANGE operator operator VARCHAR(30) NOT NULL");
        $this->addSql("ALTER TABLE account_log CHANGE account account VARCHAR(30) NOT NULL, CHANGE web web VARCHAR(30) NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE account_log CHANGE account account VARCHAR(32) NOT NULL, CHANGE web web VARCHAR(32) NOT NULL");
        $this->addSql("ALTER TABLE cash_withdraw_entry CHANGE checked_username checked_username VARCHAR(20) DEFAULT NULL");
        $this->addSql("ALTER TABLE deposit_suda_entry CHANGE checked_username checked_username VARCHAR(20) NOT NULL");
        $this->addSql("ALTER TABLE remit_entry CHANGE username username VARCHAR(20) NOT NULL, CHANGE operator operator VARCHAR(20) NOT NULL");
        $this->addSql("ALTER TABLE transcribe_entry CHANGE creator creator VARCHAR(20) DEFAULT NULL, CHANGE username username VARCHAR(20) DEFAULT NULL, CHANGE force_operator force_operator VARCHAR(20) DEFAULT NULL");
        $this->addSql("ALTER TABLE withdraw_entry_lock CHANGE operator operator VARCHAR(20) NOT NULL");
    }
}
