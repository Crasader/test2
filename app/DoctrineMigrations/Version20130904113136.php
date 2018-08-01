<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130904113136 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE payment_vendor (id INT UNSIGNED AUTO_INCREMENT NOT NULL, payment_method_id INT UNSIGNED NOT NULL, name VARCHAR(45) NOT NULL, version INT DEFAULT 1 NOT NULL, INDEX IDX_D01AA8605AA1164F (payment_method_id), PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE payment_gateway_has_payment_vendor (payment_gateway_id SMALLINT UNSIGNED NOT NULL, payment_vendor_id INT UNSIGNED NOT NULL, INDEX IDX_23A6A47762890FD5 (payment_gateway_id), INDEX IDX_23A6A477B52AC15B (payment_vendor_id), PRIMARY KEY(payment_gateway_id, payment_vendor_id))");
        $this->addSql("CREATE TABLE merchant_has_payment_vendor (merchant_id SMALLINT UNSIGNED NOT NULL, payment_vendor_id INT UNSIGNED NOT NULL, INDEX IDX_5B2B8A046796D554 (merchant_id), INDEX IDX_5B2B8A04B52AC15B (payment_vendor_id), PRIMARY KEY(merchant_id, payment_vendor_id))");
        $this->addSql("ALTER TABLE payment_vendor ADD CONSTRAINT FK_D01AA8605AA1164F FOREIGN KEY (payment_method_id) REFERENCES payment_method (id)");
        $this->addSql("ALTER TABLE payment_gateway_has_payment_vendor ADD CONSTRAINT FK_23A6A47762890FD5 FOREIGN KEY (payment_gateway_id) REFERENCES payment_gateway (id)");
        $this->addSql("ALTER TABLE payment_gateway_has_payment_vendor ADD CONSTRAINT FK_23A6A477B52AC15B FOREIGN KEY (payment_vendor_id) REFERENCES payment_vendor (id)");
        $this->addSql("ALTER TABLE merchant_has_payment_vendor ADD CONSTRAINT FK_5B2B8A046796D554 FOREIGN KEY (merchant_id) REFERENCES merchant (id)");
        $this->addSql("ALTER TABLE merchant_has_payment_vendor ADD CONSTRAINT FK_5B2B8A04B52AC15B FOREIGN KEY (payment_vendor_id) REFERENCES payment_vendor (id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE payment_gateway_has_payment_vendor DROP FOREIGN KEY FK_23A6A477B52AC15B");
        $this->addSql("ALTER TABLE merchant_has_payment_vendor DROP FOREIGN KEY FK_5B2B8A04B52AC15B");
        $this->addSql("DROP TABLE payment_gateway_has_payment_vendor");
        $this->addSql("DROP TABLE merchant_has_payment_vendor");
        $this->addSql("DROP TABLE payment_vendor");
    }
}
