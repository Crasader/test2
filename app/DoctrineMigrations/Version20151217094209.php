<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151217094209 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE merchant_withdraw_key (id INT AUTO_INCREMENT NOT NULL, merchant_withdraw_id INT UNSIGNED NOT NULL, key_type VARCHAR(20) NOT NULL, file_content VARCHAR(4096) NOT NULL, INDEX IDX_DD0C12B84B355324 (merchant_withdraw_id), PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE merchant_withdraw_key ADD CONSTRAINT FK_DD0C12B84B355324 FOREIGN KEY (merchant_withdraw_id) REFERENCES merchant_withdraw (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE merchant_withdraw_key');
    }
}
