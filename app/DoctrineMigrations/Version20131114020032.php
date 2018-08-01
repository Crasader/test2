<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131114020032 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE cash_deposit_entry ADD payway SMALLINT UNSIGNED NOT NULL AFTER domain, CHANGE currency currency SMALLINT UNSIGNED NOT NULL AFTER fee, CHANGE rate rate NUMERIC(16,8) NOT NULL AFTER currency, ADD amount_conv_basic NUMERIC(16, 4) NOT NULL AFTER rate, ADD offer_conv_basic NUMERIC(16, 4) NOT NULL AFTER amount_conv_basic, ADD fee_conv_basic NUMERIC(16, 4) NOT NULL AFTER offer_conv_basic, ADD payway_currency SMALLINT UNSIGNED NOT NULL AFTER fee_conv_basic, CHANGE user_rate payway_rate NUMERIC(16,8) NOT NULL AFTER payway_currency, ADD amount_conv NUMERIC(16, 4) NOT NULL AFTER payway_rate, ADD offer_conv NUMERIC(16, 4) NOT NULL AFTER amount_conv, ADD fee_conv NUMERIC(16, 4) NOT NULL AFTER offer_conv");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE cash_deposit_entry DROP payway_currency, DROP payway, DROP amount_conv_basic, DROP amount_conv, DROP offer_conv_basic, DROP offer_conv, DROP fee_conv_basic, DROP fee_conv, CHANGE currency currency SMALLINT UNSIGNED NOT NULL AFTER merchant_number, CHANGE rate rate NUMERIC(16,8) NOT NULL AFTER currency, CHANGE payway_rate user_rate NUMERIC(16,8) NOT NULL AFTER rate");
    }
}
