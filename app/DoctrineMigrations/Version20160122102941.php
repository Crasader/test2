<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160122102941 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE merchant_has_payment_method');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE merchant_has_payment_method (merchant_id INT UNSIGNED NOT NULL, payment_method_id INT UNSIGNED NOT NULL, INDEX IDX_F05083926796D554 (merchant_id), INDEX IDX_F05083925AA1164F (payment_method_id), PRIMARY KEY(merchant_id, payment_method_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE merchant_has_payment_method ADD CONSTRAINT FK_F05083925AA1164F FOREIGN KEY (payment_method_id) REFERENCES payment_method (id)');
        $this->addSql('ALTER TABLE merchant_has_payment_method ADD CONSTRAINT FK_F05083926796D554 FOREIGN KEY (merchant_id) REFERENCES merchant (id)');
    }
}
