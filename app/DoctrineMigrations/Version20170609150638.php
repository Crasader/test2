<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170609150638 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE remit_account ADD bb_auto_confirm TINYINT(1) NOT NULL AFTER auto_confirm');
        $this->addSql('ALTER TABLE auto_confirm_config ADD bb_enable TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE auto_remit_bank_info ADD bb_auto_confirm TINYINT(1) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE auto_remit_bank_info DROP bb_auto_confirm');
        $this->addSql('ALTER TABLE auto_confirm_config DROP bb_enable');
        $this->addSql('ALTER TABLE remit_account DROP bb_auto_confirm');
    }
}
