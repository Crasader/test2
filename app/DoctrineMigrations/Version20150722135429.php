<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150722135429 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE merchant_level (merchant_id INT UNSIGNED NOT NULL, level_id INT UNSIGNED NOT NULL, order_id SMALLINT UNSIGNED NOT NULL, version INT UNSIGNED DEFAULT 1 NOT NULL, PRIMARY KEY(merchant_id, level_id))");
        $this->addSql("CREATE TABLE merchant_level_vendor (merchant_id INT UNSIGNED NOT NULL, level_id INT UNSIGNED NOT NULL, payment_vendor_id INT UNSIGNED NOT NULL, INDEX IDX_8ACA234B52AC15B (payment_vendor_id), PRIMARY KEY(merchant_id, level_id, payment_vendor_id))");
        $this->addSql("CREATE TABLE merchant_level_method (merchant_id INT UNSIGNED NOT NULL, level_id INT UNSIGNED NOT NULL, payment_method_id INT UNSIGNED NOT NULL, INDEX IDX_A3D7ABA25AA1164F (payment_method_id), PRIMARY KEY(merchant_id, level_id, payment_method_id))");
        $this->addSql("ALTER TABLE merchant_level_vendor ADD CONSTRAINT FK_8ACA234B52AC15B FOREIGN KEY (payment_vendor_id) REFERENCES payment_vendor (id)");
        $this->addSql("ALTER TABLE merchant_level_method ADD CONSTRAINT FK_A3D7ABA25AA1164F FOREIGN KEY (payment_method_id) REFERENCES payment_method (id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE merchant_level_vendor");
        $this->addSql("DROP TABLE merchant_level_method");
        $this->addSql("DROP TABLE merchant_level");
    }
}
