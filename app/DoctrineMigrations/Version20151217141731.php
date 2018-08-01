<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151217141731 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE merchant_withdraw_stat (id INT UNSIGNED AUTO_INCREMENT NOT NULL, merchant_withdraw_id INT UNSIGNED NOT NULL, at BIGINT UNSIGNED NOT NULL, domain INT NOT NULL, count INT UNSIGNED NOT NULL, total NUMERIC(16, 4) NOT NULL, INDEX IDX_4A5F436A4B355324 (merchant_withdraw_id), INDEX idx_merchant_withdraw_stat_domain (domain), UNIQUE INDEX uni_merchant_withdraw_stat (merchant_withdraw_id, at), PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE merchant_withdraw_stat ADD CONSTRAINT FK_4A5F436A4B355324 FOREIGN KEY (merchant_withdraw_id) REFERENCES merchant_withdraw (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE merchant_withdraw_stat');
    }
}
