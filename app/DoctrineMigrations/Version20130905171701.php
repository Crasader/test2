<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130905171701 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE merchant_payment_level_vendor (merchant_id SMALLINT UNSIGNED NOT NULL, payment_level SMALLINT UNSIGNED NOT NULL, payment_vendor_id INT UNSIGNED NOT NULL, INDEX IDX_7C31EB5EB52AC15B (payment_vendor_id), PRIMARY KEY(merchant_id, payment_level, payment_vendor_id))");
        $this->addSql("CREATE TABLE merchant_payment_level_method (merchant_id SMALLINT UNSIGNED NOT NULL, payment_level SMALLINT UNSIGNED NOT NULL, payment_method_id INT UNSIGNED NOT NULL, INDEX IDX_D74AE2C85AA1164F (payment_method_id), PRIMARY KEY(merchant_id, payment_level, payment_method_id))");
        $this->addSql("ALTER TABLE merchant_payment_level_vendor ADD CONSTRAINT FK_7C31EB5EB52AC15B FOREIGN KEY (payment_vendor_id) REFERENCES payment_vendor (id)");
        $this->addSql("ALTER TABLE merchant_payment_level_method ADD CONSTRAINT FK_D74AE2C85AA1164F FOREIGN KEY (payment_method_id) REFERENCES payment_method (id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE merchant_payment_level_vendor");
        $this->addSql("DROP TABLE merchant_payment_level_method");
    }
}