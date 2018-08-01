<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140618112648 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE merchant_suda (id SMALLINT UNSIGNED AUTO_INCREMENT NOT NULL, domain INT NOT NULL, login_alias VARCHAR(45) NOT NULL, alias VARCHAR(45) NOT NULL, private_key1 VARCHAR(512) NOT NULL, private_key2 VARCHAR(512) NOT NULL, type VARCHAR(5) NOT NULL, enable TINYINT(1) NOT NULL, removed TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE deposit_suda_entry (id INT UNSIGNED AUTO_INCREMENT NOT NULL, seq_id INT UNSIGNED NOT NULL, user_id INT NOT NULL, domain INT NOT NULL, merchant_number INT UNSIGNED NOT NULL, order_id VARCHAR(40) NOT NULL, code VARCHAR(5) NOT NULL, alias VARCHAR(45) NOT NULL, amount NUMERIC(16, 4) NOT NULL, offer_deposit NUMERIC(16, 4) NOT NULL, offer_other NUMERIC(16, 4) NOT NULL, bank_info_id INT NOT NULL, recipient VARCHAR(100) NOT NULL, account VARCHAR(36) NOT NULL, fee NUMERIC(16, 4) NOT NULL, merchant_suda_id SMALLINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL, checked_username VARCHAR(20) NOT NULL, confirm_at DATETIME DEFAULT NULL, confirm TINYINT(1) NOT NULL, cancel TINYINT(1) NOT NULL, memo VARCHAR(100) DEFAULT '' NOT NULL, PRIMARY KEY(id))");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE merchant_suda");
        $this->addSql("DROP TABLE deposit_suda_entry");
    }
}
