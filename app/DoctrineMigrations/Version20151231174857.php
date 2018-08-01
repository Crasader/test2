<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151231174857 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE deposit_stat');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE deposit_stat (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, month INT UNSIGNED NOT NULL, total NUMERIC(16, 4) NOT NULL, max_amount NUMERIC(16, 4) NOT NULL, update_at DATETIME NOT NULL, version INT DEFAULT 1 NOT NULL, counts INT UNSIGNED NOT NULL, UNIQUE INDEX uni_deposit_stat (user_id, month), INDEX idx_deposit_stat_update_at (update_at), PRIMARY KEY(id))');
    }
}
