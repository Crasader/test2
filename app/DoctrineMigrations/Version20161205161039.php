<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161205161039 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_stat ADD modified_at DATETIME DEFAULT NOW() NOT NULL AFTER user_id, ADD first_deposit_at BIGINT UNSIGNED DEFAULT 0 NOT NULL, ADD first_deposit_amount NUMERIC(16, 4) NOT NULL');
        $this->addSql('CREATE INDEX idx_user_stat_modified_at ON user_stat (modified_at)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX idx_user_stat_modified_at ON user_stat');
        $this->addSql('ALTER TABLE user_stat DROP modified_at, DROP first_deposit_at, DROP first_deposit_amount');
    }
}
