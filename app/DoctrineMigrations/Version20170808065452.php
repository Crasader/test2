<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170808065452 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE auto_remit_has_bank_info (auto_remit_id SMALLINT UNSIGNED NOT NULL, bank_info_id INT NOT NULL, INDEX IDX_6E2B89BEC9EA9468 (auto_remit_id), INDEX IDX_6E2B89BE731FA956 (bank_info_id), PRIMARY KEY(auto_remit_id, bank_info_id))');
        $this->addSql('ALTER TABLE auto_remit_has_bank_info ADD CONSTRAINT FK_6E2B89BEC9EA9468 FOREIGN KEY (auto_remit_id) REFERENCES auto_remit (id)');
        $this->addSql('ALTER TABLE auto_remit_has_bank_info ADD CONSTRAINT FK_6E2B89BE731FA956 FOREIGN KEY (bank_info_id) REFERENCES bank_info (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE auto_remit_has_bank_info');
    }
}
