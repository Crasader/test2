<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151214151207 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE merchant_withdraw (id INT UNSIGNED AUTO_INCREMENT NOT NULL, payment_gateway_id SMALLINT UNSIGNED NOT NULL, alias VARCHAR(45) NOT NULL, number VARCHAR(80) NOT NULL, enable TINYINT(1) NOT NULL, approved TINYINT(1) NOT NULL, currency SMALLINT UNSIGNED NOT NULL, private_key VARCHAR(512) NOT NULL, shop_url VARCHAR(100) NOT NULL, web_url VARCHAR(100) NOT NULL, full_set TINYINT(1) NOT NULL, created_by_admin TINYINT(1) NOT NULL, suspend TINYINT(1) NOT NULL, removed TINYINT(1) NOT NULL, bind_shop TINYINT(1) NOT NULL, domain INT NOT NULL, INDEX IDX_D2DA2B2762890FD5 (payment_gateway_id), PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE merchant_withdraw ADD CONSTRAINT FK_D2DA2B2762890FD5 FOREIGN KEY (payment_gateway_id) REFERENCES payment_gateway (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE merchant_withdraw');
    }
}
