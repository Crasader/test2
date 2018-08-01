<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161101150848 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE auto_remit_bank_info (bank_info_id INT NOT NULL, code VARCHAR(10) NOT NULL, order_id SMALLINT UNSIGNED NOT NULL, PRIMARY KEY(bank_info_id))');
        $this->addSql('ALTER TABLE auto_remit_bank_info ADD CONSTRAINT FK_E9621992731FA956 FOREIGN KEY (bank_info_id) REFERENCES bank_info (id)');
        $this->addSql("INSERT INTO auto_remit_bank_info (`bank_info_id`, `code`, `order_id`) VALUES ('5', 'CMB', '1'), ('6', 'CMBC', '2'), ('4', 'CCB', '3'), ('1', 'ICBC', '4'), ('2', 'BCM', '5'), ('3', 'ABC', '6'), ('10', 'CIB', '7'), ('11', 'CNCB', '8'), ('13', 'HXB', '9'), ('15', 'PAB', '10'), ('16', 'PSBC', '11')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE auto_remit_bank_info');
    }
}
