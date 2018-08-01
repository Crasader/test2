<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171013183724 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE deposit_bitcoin (id INT UNSIGNED AUTO_INCREMENT NOT NULL, payment_charge_id INT UNSIGNED NOT NULL, discount SMALLINT UNSIGNED NOT NULL, discount_give_up TINYINT(1) NOT NULL, discount_amount NUMERIC(16, 4) NOT NULL, discount_percent NUMERIC(5, 2) NOT NULL, discount_factor SMALLINT UNSIGNED NOT NULL, discount_limit NUMERIC(16, 4) NOT NULL, deposit_max NUMERIC(16, 4) NOT NULL, deposit_min NUMERIC(16, 4) NOT NULL, audit_live TINYINT(1) NOT NULL, audit_live_amount NUMERIC(16, 4) NOT NULL, audit_ball TINYINT(1) NOT NULL, audit_ball_amount NUMERIC(16, 4) NOT NULL, audit_complex TINYINT(1) NOT NULL, audit_complex_amount NUMERIC(16, 4) NOT NULL, audit_normal TINYINT(1) NOT NULL, audit_normal_amount NUMERIC(5, 2) NOT NULL, audit_3d TINYINT(1) NOT NULL, audit_3d_amount NUMERIC(16, 4) NOT NULL, audit_battle TINYINT(1) NOT NULL, audit_battle_amount NUMERIC(16, 4) NOT NULL, audit_virtual TINYINT(1) NOT NULL, audit_virtual_amount NUMERIC(16, 4) NOT NULL, audit_discount_amount NUMERIC(5, 2) NOT NULL, audit_loosen NUMERIC(16, 4) NOT NULL, audit_administrative NUMERIC(5, 2) NOT NULL, version INT DEFAULT 1 NOT NULL, bitcoin_fee_max NUMERIC(16, 4) NOT NULL, bitcoin_fee_percent NUMERIC(5, 2) NOT NULL, UNIQUE INDEX UNIQ_D70B28351601663C (payment_charge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE deposit_bitcoin ADD CONSTRAINT FK_D70B28351601663C FOREIGN KEY (payment_charge_id) REFERENCES payment_charge (id)');
        $this->addSql('ALTER TABLE payment_withdraw_fee ADD bitcoin_free_period SMALLINT NOT NULL AFTER mobile_withdraw_min, ADD bitcoin_free_count SMALLINT NOT NULL AFTER bitcoin_free_period, ADD bitcoin_amount_max NUMERIC(16, 4) NOT NULL AFTER bitcoin_free_count, ADD bitcoin_amount_percent NUMERIC(5, 2) NOT NULL AFTER bitcoin_amount_max, ADD bitcoin_withdraw_max NUMERIC(16, 4) NOT NULL AFTER bitcoin_amount_percent, ADD bitcoin_withdraw_min NUMERIC(16, 4) NOT NULL AFTER bitcoin_withdraw_max');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE deposit_bitcoin');
        $this->addSql('ALTER TABLE payment_withdraw_fee DROP bitcoin_free_period, DROP bitcoin_free_count, DROP bitcoin_amount_max, DROP bitcoin_amount_percent, DROP bitcoin_withdraw_max, DROP bitcoin_withdraw_min');
    }
}
