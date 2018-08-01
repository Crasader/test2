<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170215105708 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE payment_deposit_withdraw_entry (id BIGINT NOT NULL, at BIGINT NOT NULL, merchant_id INT UNSIGNED DEFAULT 0 NOT NULL, remit_account_id INT UNSIGNED DEFAULT 0 NOT NULL, domain INT NOT NULL, user_id BIGINT NOT NULL, ref_id BIGINT DEFAULT 0 NOT NULL, currency SMALLINT NOT NULL, opcode INT NOT NULL, amount NUMERIC(16, 4) NOT NULL, balance NUMERIC(16, 4) NOT NULL, memo VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'\' NOT NULL, INDEX idx_payment_deposit_withdraw_entry_at (at), INDEX idx_payment_deposit_withdraw_entry_ref_id (ref_id), INDEX idx_payment_deposit_withdraw_entry_merchant_id (merchant_id), INDEX idx_payment_deposit_withdraw_entry_remit_account_id (remit_account_id), INDEX idx_payment_deposit_withdraw_entry_domain_at (domain, at), INDEX idx_payment_deposit_withdraw_entry_user_id_at (user_id, at), PRIMARY KEY(id, at))');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE payment_deposit_withdraw_entry');
    }
}
