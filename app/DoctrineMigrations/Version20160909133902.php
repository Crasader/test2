<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160909133902 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT INTO merchant_extra (merchant_id, name, value) SELECT id, 'bundleID', '' FROM merchant m WHERE m.payment_gateway_id = 92 AND removed = 0");
        $this->addSql("INSERT INTO merchant_extra (merchant_id, name, value) SELECT id, 'applyID', '' FROM merchant m WHERE m.payment_gateway_id = 92 AND removed = 0");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES (92, 'bundleID', ''), (92, 'applyID', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = '92' AND name in ('bundleID', 'applyID')");
        $this->addSql("DELETE FROM merchant_extra WHERE merchant_id in (SELECT id FROM merchant WHERE payment_gateway_id = 92 AND removed = 0) AND name = 'bundleID' AND value = ''");
        $this->addSql("DELETE FROM merchant_extra WHERE merchant_id in (SELECT id FROM merchant WHERE payment_gateway_id = 92 AND removed = 0) AND name = 'applyID' AND value = ''");
    }
}
