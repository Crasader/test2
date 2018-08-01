<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171011081043 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE bitcoin_wallet (id INT UNSIGNED AUTO_INCREMENT NOT NULL, domain INT NOT NULL, wallet_code VARCHAR(64) NOT NULL, password VARCHAR(64) NOT NULL, second_password VARCHAR(64) DEFAULT NULL, api_code VARCHAR(64) NOT NULL, INDEX idx_bitcoin_wallet_domain (domain), PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE bitcoin_address (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id BIGINT NOT NULL, wallet_id INT NOT NULL, account VARCHAR(256) NOT NULL, address VARCHAR(64) NOT NULL, INDEX idx_bitcoin_address_user_id (user_id), UNIQUE INDEX uni_user_id (user_id), PRIMARY KEY(id))');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE bitcoin_wallet');
        $this->addSql('DROP TABLE bitcoin_address');
    }
}
