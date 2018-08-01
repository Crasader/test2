<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170809030623 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE cash_transfer_entry');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE cash_transfer_entry (id BIGINT NOT NULL, at BIGINT NOT NULL, cash_id INT NOT NULL, user_id INT NOT NULL, currency SMALLINT NOT NULL, opcode INT NOT NULL, created_at DATETIME NOT NULL, amount NUMERIC(16, 4) NOT NULL, balance NUMERIC(16, 4) NOT NULL, ref_id BIGINT DEFAULT 0 NOT NULL, memo VARCHAR(100) DEFAULT \'\' NOT NULL, tag VARCHAR(50) DEFAULT NULL, INDEX idx_cash_transfer_entry_ref_id (ref_id), INDEX idx_cash_transfer_entry_at (at), INDEX idx_cash_transfer_entry_tag (tag), INDEX idx_cash_transfer_entry_user_id_at (user_id, at), PRIMARY KEY(id, at))');
    }
}
