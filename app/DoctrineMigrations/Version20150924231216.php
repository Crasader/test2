<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150924231216 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE card_charge (id INT UNSIGNED AUTO_INCREMENT NOT NULL, domain INT NOT NULL, order_strategy SMALLINT UNSIGNED NOT NULL, deposit_sc_max NUMERIC(16, 4) NOT NULL, deposit_sc_min NUMERIC(16, 4) NOT NULL, deposit_co_max NUMERIC(16, 4) NOT NULL, deposit_co_min NUMERIC(16, 4) NOT NULL, deposit_sa_max NUMERIC(16, 4) NOT NULL, deposit_sa_min NUMERIC(16, 4) NOT NULL, deposit_ag_max NUMERIC(16, 4) NOT NULL, deposit_ag_min NUMERIC(16, 4) NOT NULL, version INT DEFAULT 1 NOT NULL, UNIQUE INDEX uni_card_charge (domain), PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE card_deposit_entry (id BIGINT UNSIGNED NOT NULL, at BIGINT NOT NULL, user_id INT NOT NULL, user_role SMALLINT NOT NULL, domain INT NOT NULL, amount NUMERIC(16, 4) NOT NULL, fee NUMERIC(16, 4) NOT NULL, currency SMALLINT UNSIGNED NOT NULL, rate NUMERIC(16, 8) NOT NULL, payway_currency SMALLINT UNSIGNED NOT NULL, payway_rate NUMERIC(16, 8) NOT NULL, amount_conv_basic NUMERIC(16, 4) NOT NULL, fee_conv_basic NUMERIC(16, 4) NOT NULL, amount_conv NUMERIC(16, 4) NOT NULL, fee_conv NUMERIC(16, 4) NOT NULL, telephone VARCHAR(20) NOT NULL, postcode VARCHAR(12) NOT NULL, address VARCHAR(254) NOT NULL, email VARCHAR(50) NOT NULL, web_shop TINYINT(1) NOT NULL, merchant_card_id INT UNSIGNED NOT NULL, merchant_card_number VARCHAR(80) NOT NULL, payment_method_id INT UNSIGNED NOT NULL, payment_vendor_id INT UNSIGNED NOT NULL, memo VARCHAR(500) NOT NULL, entry_id BIGINT UNSIGNED DEFAULT NULL, fee_entry_id BIGINT UNSIGNED DEFAULT NULL, manual TINYINT(1) NOT NULL, confirm TINYINT(1) NOT NULL, confirm_at DATETIME DEFAULT NULL, version INT UNSIGNED DEFAULT 1 NOT NULL, INDEX idx_card_deposit_entry_user_id (user_id), INDEX idx_card_deposit_entry_at (at), INDEX idx_card_deposit_entry_confirm_at (confirm_at), INDEX idx_card_deposit_entry_domain_at (domain, at), PRIMARY KEY(id, at))');
        $this->addSql('CREATE TABLE card_deposit_tracking (entry_id BIGINT UNSIGNED NOT NULL, payment_gateway_id SMALLINT UNSIGNED NOT NULL, retry SMALLINT UNSIGNED NOT NULL, PRIMARY KEY(entry_id))');
        $this->addSql('CREATE TABLE card_payment_gateway_fee (card_charge_id INT UNSIGNED NOT NULL, payment_gateway_id SMALLINT UNSIGNED NOT NULL, rate NUMERIC(8, 4) NOT NULL, INDEX IDX_73C0D7E32120AA40 (card_charge_id), INDEX IDX_73C0D7E362890FD5 (payment_gateway_id), PRIMARY KEY(card_charge_id, payment_gateway_id))');
        $this->addSql('ALTER TABLE card_payment_gateway_fee ADD CONSTRAINT FK_73C0D7E32120AA40 FOREIGN KEY (card_charge_id) REFERENCES card_charge (id)');
        $this->addSql('ALTER TABLE card_payment_gateway_fee ADD CONSTRAINT FK_73C0D7E362890FD5 FOREIGN KEY (payment_gateway_id) REFERENCES payment_gateway (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE card_deposit_entry');
        $this->addSql('DROP TABLE card_deposit_tracking');
        $this->addSql('DROP TABLE card_payment_gateway_fee');
        $this->addSql('DROP TABLE card_charge');
    }
}
