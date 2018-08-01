<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180131154640 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE domain_config ADD free_transfer_wallet TINYINT(1) DEFAULT \'0\' NOT NULL, ADD wallet_status SMALLINT NOT NULL');
        $this->addSql('CREATE TABLE user_last_game (user_id BIGINT UNSIGNED NOT NULL, enable TINYINT(1) NOT NULL, last_game_code SMALLINT UNSIGNED DEFAULT 1 NOT NULL, modified_at DATETIME DEFAULT NULL, version INT UNSIGNED DEFAULT 1 NOT NULL, PRIMARY KEY(user_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE domain_config DROP free_transfer_wallet, DROP wallet_status');
        $this->addSql('DROP TABLE user_last_game');
    }
}
