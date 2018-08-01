<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140716114940 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE payment_gateway_bind_ip (id INT UNSIGNED AUTO_INCREMENT NOT NULL, payment_gateway_id SMALLINT UNSIGNED NOT NULL, ip INT UNSIGNED NOT NULL, INDEX IDX_A66BB3ED62890FD5 (payment_gateway_id), PRIMARY KEY(id))");
        $this->addSql("ALTER TABLE payment_gateway_bind_ip ADD CONSTRAINT FK_A66BB3ED62890FD5 FOREIGN KEY (payment_gateway_id) REFERENCES payment_gateway (id)");
        $this->addSql("ALTER TABLE payment_gateway ADD bind_ip TINYINT(1) NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE payment_gateway_bind_ip");
        $this->addSql("ALTER TABLE payment_gateway DROP bind_ip");
    }
}
