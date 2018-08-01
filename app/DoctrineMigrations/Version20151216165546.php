<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151216165546 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE merchant_withdraw_level_bank_info (merchant_withdraw_id INT UNSIGNED NOT NULL, level_id INT UNSIGNED NOT NULL, bank_info_id INT NOT NULL, INDEX IDX_9968BDE5731FA956 (bank_info_id), PRIMARY KEY(merchant_withdraw_id, level_id, bank_info_id))');
        $this->addSql('ALTER TABLE merchant_withdraw_level_bank_info ADD CONSTRAINT FK_9968BDE5731FA956 FOREIGN KEY (bank_info_id) REFERENCES bank_info (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE merchant_withdraw_level_bank_info');
    }
}
