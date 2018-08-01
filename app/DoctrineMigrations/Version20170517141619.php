<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170517141619 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE remit_account_stat (id INT UNSIGNED AUTO_INCREMENT NOT NULL, remit_account_id INT UNSIGNED NOT NULL, at BIGINT UNSIGNED NOT NULL, count SMALLINT UNSIGNED NOT NULL, UNIQUE INDEX uni_remit_account_stat (remit_account_id, at), PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE remit_level_order (id INT UNSIGNED AUTO_INCREMENT NOT NULL, domain INT NOT NULL, level_id INT UNSIGNED NOT NULL, by_count TINYINT(1) NOT NULL, version INT UNSIGNED DEFAULT 1 NOT NULL, INDEX idx_remit_level_order_domain (domain), INDEX idx_remit_level_order_level_id (level_id), PRIMARY KEY(id))');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE remit_level_order');
        $this->addSql('DROP TABLE remit_account_stat');
    }
}
