<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170420103551 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE bodog_user (id BIGINT NOT NULL, external_id BIGINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id, external_id))');
        $this->addSql("CREATE TABLE outside_entry (id BIGINT NOT NULL, created_at BIGINT NOT NULL, outside_id BIGINT NOT NULL, user_id BIGINT NOT NULL, currency SMALLINT UNSIGNED NOT NULL, opcode INT NOT NULL, amount NUMERIC(16, 4) NOT NULL, balance NUMERIC(16, 4) NOT NULL, ref_id BIGINT DEFAULT 0 NOT NULL, memo VARCHAR(100) DEFAULT '' NOT NULL, group_num INT NOT NULL, INDEX idx_outside_entry_created_at (created_at), INDEX idx_outside_entry_user_id_created_at (user_id, created_at), INDEX idx_outside_entry_ref_id (ref_id), PRIMARY KEY(id, created_at))");
        $this->addSql("CREATE TABLE outside_trans (id BIGINT NOT NULL, entry_id BIGINT NOT NULL, user_id BIGINT NOT NULL, currency SMALLINT UNSIGNED NOT NULL, opcode INT NOT NULL, amount NUMERIC(16, 4) NOT NULL, ref_id BIGINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, checked TINYINT(1) NOT NULL, checked_at DATETIME DEFAULT NULL, memo VARCHAR(100) DEFAULT '' NOT NULL, group_num INT NOT NULL, INDEX idx_outside_trans_created_at (created_at), INDEX idx_outside_trans_checked (checked), INDEX idx_outside_trans_ref_id (ref_id), PRIMARY KEY(id))");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE bodog_user');
        $this->addSql('DROP TABLE outside_entry');
        $this->addSql('DROP TABLE outside_trans');
    }
}
