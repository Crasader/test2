<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170717061734 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE blacklist ADD system_lock TINYINT(1) DEFAULT \'0\' NOT NULL, ADD control_terminal TINYINT(1) DEFAULT \'0\' NOT NULL, DROP note');
        $this->addSql('CREATE INDEX idx_blacklist_system_lock ON blacklist (system_lock)');
        $this->addSql('CREATE INDEX idx_blacklist_control_terminal ON blacklist (control_terminal)');
        $this->addSql('ALTER TABLE blacklist_operation_log ADD note VARCHAR(150) DEFAULT NULL');
        $this->addSql('ALTER TABLE removed_blacklist ADD system_lock TINYINT(1) DEFAULT \'0\' NOT NULL, ADD control_terminal TINYINT(1) DEFAULT \'0\' NOT NULL, DROP note');
        $this->addSql('CREATE INDEX idx_removed_blacklist_system_lock ON removed_blacklist (system_lock)');
        $this->addSql('CREATE INDEX idx_removed_blacklist_control_terminal ON removed_blacklist (control_terminal)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX idx_blacklist_system_lock ON blacklist');
        $this->addSql('DROP INDEX idx_blacklist_control_terminal ON blacklist');
        $this->addSql('ALTER TABLE blacklist ADD note VARCHAR(150) DEFAULT NULL, DROP system_lock, DROP control_terminal');
        $this->addSql('ALTER TABLE blacklist_operation_log DROP note');
        $this->addSql('DROP INDEX idx_removed_blacklist_system_lock ON removed_blacklist');
        $this->addSql('DROP INDEX idx_removed_blacklist_control_terminal ON removed_blacklist');
        $this->addSql('ALTER TABLE removed_blacklist ADD note VARCHAR(150) DEFAULT NULL, DROP system_lock, DROP control_terminal');
    }
}
