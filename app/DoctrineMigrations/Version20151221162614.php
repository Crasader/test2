<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151221162614 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE payment_gateway_has_bank_info (payment_gateway_id SMALLINT UNSIGNED NOT NULL, bank_info_id INT NOT NULL, INDEX IDX_51B68F9F62890FD5 (payment_gateway_id), INDEX IDX_51B68F9F731FA956 (bank_info_id), PRIMARY KEY(payment_gateway_id, bank_info_id))');
        $this->addSql('ALTER TABLE payment_gateway_has_bank_info ADD CONSTRAINT FK_51B68F9F62890FD5 FOREIGN KEY (payment_gateway_id) REFERENCES payment_gateway (id)');
        $this->addSql('ALTER TABLE payment_gateway_has_bank_info ADD CONSTRAINT FK_51B68F9F731FA956 FOREIGN KEY (bank_info_id) REFERENCES bank_info (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE payment_gateway_has_bank_info');
    }
}
