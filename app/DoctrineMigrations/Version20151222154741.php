<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151222154741 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE merchant_payment_level');
        $this->addSql('DROP TABLE merchant_payment_level_method');
        $this->addSql('DROP TABLE merchant_payment_level_vendor');
        $this->addSql('DROP TABLE payment_level');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE merchant_payment_level (merchant_id INT UNSIGNED NOT NULL, payment_level SMALLINT UNSIGNED NOT NULL, order_id SMALLINT UNSIGNED NOT NULL, version INT DEFAULT 1 NOT NULL, PRIMARY KEY(merchant_id, payment_level))');
        $this->addSql('CREATE TABLE merchant_payment_level_method (merchant_id INT UNSIGNED NOT NULL, payment_level SMALLINT UNSIGNED NOT NULL, payment_method_id INT UNSIGNED NOT NULL, INDEX IDX_D74AE2C85AA1164F (payment_method_id), PRIMARY KEY(merchant_id, payment_level, payment_method_id))');
        $this->addSql('CREATE TABLE merchant_payment_level_vendor (merchant_id INT UNSIGNED NOT NULL, payment_level SMALLINT UNSIGNED NOT NULL, payment_vendor_id INT UNSIGNED NOT NULL, INDEX IDX_7C31EB5EB52AC15B (payment_vendor_id), PRIMARY KEY(merchant_id, payment_level, payment_vendor_id))');
        $this->addSql('CREATE TABLE payment_level (domain INT NOT NULL, level SMALLINT UNSIGNED NOT NULL, order_strategy SMALLINT UNSIGNED NOT NULL, PRIMARY KEY(domain, level))');
    }
}
