<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141107143412 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE merchant ADD payway SMALLINT UNSIGNED NOT NULL AFTER payment_gateway_id");
        $this->addSql("UPDATE merchant SET payway = 1");
        $this->addSql("UPDATE merchant SET payway = 2 WHERE agent = 1");
        $this->addSql("ALTER TABLE merchant DROP agent");
        $this->addSql("ALTER TABLE payment_charge ADD payway SMALLINT UNSIGNED NOT NULL AFTER id");
        $this->addSql("UPDATE payment_charge SET payway = 1");
        $this->addSql("UPDATE payment_charge SET payway = 2 WHERE agent = 1");
        $this->addSql("ALTER TABLE payment_charge DROP agent");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE merchant ADD agent TINYINT(1) NOT NULL AFTER approved");
        $this->addSql("UPDATE merchant SET agent = 1 WHERE payway = 2");
        $this->addSql("ALTER TABLE merchant DROP payway");
        $this->addSql("ALTER TABLE payment_charge ADD agent TINYINT(1) NOT NULL AFTER code");
        $this->addSql("UPDATE payment_charge SET agent = 1 WHERE payway = 2");
        $this->addSql("ALTER TABLE payment_charge DROP payway");
    }
}
