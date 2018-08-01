<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171006055713 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE auto_confirm_config');
        $this->addSql('DROP TABLE auto_remit_bank_info');
        $this->addSql('ALTER TABLE remit_entry DROP bb_auto_confirm');
        $this->addSql('ALTER TABLE remit_account DROP bb_auto_confirm');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE auto_confirm_config (domain INT NOT NULL, host VARCHAR(100) NOT NULL COLLATE latin1_swedish_ci, api_key VARCHAR(100) NOT NULL COLLATE latin1_swedish_ci, enable TINYINT(1) DEFAULT \'1\' NOT NULL, bb_enable TINYINT(1) NOT NULL, PRIMARY KEY(domain))');
        $this->addSql('CREATE TABLE auto_remit_bank_info (bank_info_id INT NOT NULL, code VARCHAR(10) NOT NULL COLLATE latin1_swedish_ci, order_id SMALLINT UNSIGNED NOT NULL, bb_auto_confirm TINYINT(1) NOT NULL, PRIMARY KEY(bank_info_id))');
        $this->addSql('ALTER TABLE auto_remit_bank_info ADD CONSTRAINT FK_E9621992731FA956 FOREIGN KEY (bank_info_id) REFERENCES bank_info (id)');
        $this->addSql('ALTER TABLE remit_account ADD bb_auto_confirm TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE remit_entry ADD bb_auto_confirm TINYINT(1) NOT NULL');
    }
}
