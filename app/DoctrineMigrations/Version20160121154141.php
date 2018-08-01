<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160121154141 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE merchant_has_payment_vendor');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE merchant_has_payment_vendor (merchant_id INT UNSIGNED NOT NULL, payment_vendor_id INT UNSIGNED NOT NULL, INDEX IDX_5B2B8A046796D554 (merchant_id), INDEX IDX_5B2B8A04B52AC15B (payment_vendor_id), PRIMARY KEY(merchant_id, payment_vendor_id))');
        $this->addSql('ALTER TABLE merchant_has_payment_vendor ADD CONSTRAINT FK_5B2B8A046796D554 FOREIGN KEY (merchant_id) REFERENCES merchant (id)');
        $this->addSql('ALTER TABLE merchant_has_payment_vendor ADD CONSTRAINT FK_5B2B8A04B52AC15B FOREIGN KEY (payment_vendor_id) REFERENCES payment_vendor (id)');
    }
}
