<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130904100907 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE payment_method (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(45) NOT NULL, version INT DEFAULT 1 NOT NULL, PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE payment_gateway_has_payment_method (payment_gateway_id SMALLINT UNSIGNED NOT NULL, payment_method_id INT UNSIGNED NOT NULL, INDEX IDX_88DDADE162890FD5 (payment_gateway_id), INDEX IDX_88DDADE15AA1164F (payment_method_id), PRIMARY KEY(payment_gateway_id, payment_method_id))");
        $this->addSql("CREATE TABLE merchant_has_payment_method (merchant_id SMALLINT UNSIGNED NOT NULL, payment_method_id INT UNSIGNED NOT NULL, INDEX IDX_F05083926796D554 (merchant_id), INDEX IDX_F05083925AA1164F (payment_method_id), PRIMARY KEY(merchant_id, payment_method_id))");
        $this->addSql("ALTER TABLE payment_gateway_has_payment_method ADD CONSTRAINT FK_88DDADE162890FD5 FOREIGN KEY (payment_gateway_id) REFERENCES payment_gateway (id)");
        $this->addSql("ALTER TABLE payment_gateway_has_payment_method ADD CONSTRAINT FK_88DDADE15AA1164F FOREIGN KEY (payment_method_id) REFERENCES payment_method (id)");
        $this->addSql("ALTER TABLE merchant_has_payment_method ADD CONSTRAINT FK_F05083926796D554 FOREIGN KEY (merchant_id) REFERENCES merchant (id)");
        $this->addSql("ALTER TABLE merchant_has_payment_method ADD CONSTRAINT FK_F05083925AA1164F FOREIGN KEY (payment_method_id) REFERENCES payment_method (id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE payment_gateway_has_payment_method DROP FOREIGN KEY FK_88DDADE15AA1164F");
        $this->addSql("ALTER TABLE merchant_has_payment_method DROP FOREIGN KEY FK_F05083925AA1164F");
        $this->addSql("DROP TABLE payment_method");
        $this->addSql("DROP TABLE payment_gateway_has_payment_method");
        $this->addSql("DROP TABLE merchant_has_payment_method");
    }
}
