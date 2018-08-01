<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161213115428 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql('DROP TABLE deposit_error');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql('CREATE TABLE deposit_error (id INT UNSIGNED AUTO_INCREMENT NOT NULL, entry_id BIGINT UNSIGNED NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL, payment_gateway_id SMALLINT UNSIGNED NOT NULL, merchant_id INT UNSIGNED NOT NULL, merchant_number VARCHAR(80) NOT NULL, domain INT NOT NULL, payment_vendor_id INT UNSIGNED NOT NULL, message VARCHAR(50) NOT NULL, INDEX idx_deposit_error_created_at (created_at), PRIMARY KEY(id))');
    }
}
