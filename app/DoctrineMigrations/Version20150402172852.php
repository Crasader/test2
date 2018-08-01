<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150402172852 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE stat_cash_deposit_withdraw DROP version");
        $this->addSql("ALTER TABLE stat_cash_opcode DROP version");
        $this->addSql("ALTER TABLE stat_cash_all_offer DROP version");
        $this->addSql("ALTER TABLE stat_cash_remit DROP version");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE stat_cash_all_offer ADD version INT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE stat_cash_deposit_withdraw ADD version INT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE stat_cash_opcode ADD version INT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE stat_cash_remit ADD version INT UNSIGNED NOT NULL");
    }
}
