<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170620111330 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bodog_user CHANGE id id BIGINT UNSIGNED NOT NULL, CHANGE external_id external_id BIGINT UNSIGNED DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE outside_trans CHANGE user_id user_id BIGINT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE outside_entry CHANGE user_id user_id BIGINT UNSIGNED NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bodog_user CHANGE id id BIGINT NOT NULL, CHANGE external_id external_id BIGINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE outside_entry CHANGE user_id user_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE outside_trans CHANGE user_id user_id BIGINT NOT NULL');
    }
}
