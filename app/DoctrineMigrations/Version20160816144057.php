<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160816144057 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO merchant_extra (merchant_id, name, value) SELECT id, 'remark', '' FROM merchant WHERE payment_gateway_id = 92 AND removed = 0");
        $this->addSql("INSERT INTO payment_gateway_description (payment_gateway_id, name, value) VALUES (92, 'remark', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM payment_gateway_description WHERE payment_gateway_id = 92 AND name = 'remark'");
        $this->addSql("DELETE FROM merchant_extra WHERE merchant_id IN (SELECT id FROM merchant WHERE payment_gateway_id = 92 AND removed = 0) AND name = 'remark'");
    }
}
