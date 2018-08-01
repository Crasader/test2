<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130819152143 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        $this->addSql("ALTER TABLE cash_fake_trans ADD user_id INT NOT NULL AFTER cash_fake_id, ADD currency SMALLINT UNSIGNED NOT NULL AFTER user_id");
        $this->addSql("ALTER TABLE cash_trans ADD user_id INT NOT NULL AFTER cash_id, ADD currency SMALLINT UNSIGNED NOT NULL AFTER user_id");
        $this->addSql("ALTER TABLE cash_fake_entry ADD user_id INT NOT NULL AFTER cash_fake_id, ADD currency SMALLINT UNSIGNED NOT NULL AFTER user_id");
        $this->addSql("ALTER TABLE cash_fake_error ADD user_id INT NOT NULL AFTER cash_fake_id, ADD currency SMALLINT UNSIGNED NOT NULL AFTER user_id");
        $this->addSql("ALTER TABLE cash_entry ADD user_id INT NOT NULL AFTER cash_id, ADD currency SMALLINT UNSIGNED NOT NULL AFTER user_id");
        $this->addSql("ALTER TABLE cash_error ADD user_id INT NOT NULL AFTER cash_id, ADD currency SMALLINT UNSIGNED NOT NULL AFTER user_id");
        $this->addSql("ALTER TABLE cash_withdraw_entry ADD user_id INT NOT NULL AFTER cash_id, ADD currency SMALLINT UNSIGNED NOT NULL AFTER user_id");
        $this->addSql("CREATE INDEX idx_cash_entry_user_id_at ON cash_entry (user_id, at)");
        $this->addSql("CREATE INDEX idx_cash_fake_entry_user_id_at ON cash_fake_entry (user_id, at)");
        $this->addSql("CREATE INDEX idx_cash_withdraw_entry_user_id_at ON cash_withdraw_entry (user_id, at)");
        $this->addSql("UPDATE cash_entry ce, cash c SET ce.currency = c.currency, ce.user_id = c.user_id WHERE ce.cash_id = c.id");
        $this->addSql("UPDATE cash_trans ct, cash c SET ct.currency = c.currency, ct.user_id = c.user_id WHERE ct.cash_id = c.id");
        $this->addSql("UPDATE cash_error ce, cash c SET ce.currency = c.currency, ce.user_id = c.user_id WHERE ce.cash_id = c.id");
        $this->addSql("UPDATE cash_withdraw_entry cwe, cash c SET cwe.currency = c.currency, cwe.user_id = c.user_id WHERE cwe.cash_id = c.id");
        $this->addSql("UPDATE cash_fake_entry cfe, cash_fake cf SET cfe.currency = cf.currency, cfe.user_id = cf.user_id WHERE cfe.cash_fake_id = cf.id");
        $this->addSql("UPDATE cash_fake_trans cft, cash_fake cf SET cft.currency = cf.currency, cft.user_id = cf.user_id WHERE cft.cash_fake_id = cf.id");
        $this->addSql("UPDATE cash_fake_error cfe, cash_fake cf SET cfe.currency = cf.currency, cfe.user_id = cf.user_id WHERE cfe.cash_fake_id = cf.id");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        $this->addSql("ALTER TABLE cash_entry DROP INDEX idx_cash_entry_user_id_at");
        $this->addSql("ALTER TABLE cash_fake_entry DROP INDEX idx_cash_fake_entry_user_id_at");
        $this->addSql("ALTER TABLE cash_withdraw_entry DROP INDEX idx_cash_withdraw_entry_user_id_at");
        $this->addSql("ALTER TABLE cash_entry DROP user_id, DROP currency");
        $this->addSql("ALTER TABLE cash_error DROP user_id, DROP currency");
        $this->addSql("ALTER TABLE cash_fake_entry DROP user_id, DROP currency");
        $this->addSql("ALTER TABLE cash_fake_error DROP user_id, DROP currency");
        $this->addSql("ALTER TABLE cash_fake_trans DROP user_id, DROP currency");
        $this->addSql("ALTER TABLE cash_trans DROP user_id, DROP currency");
        $this->addSql("ALTER TABLE cash_withdraw_entry DROP user_id, DROP currency");
    }
}
