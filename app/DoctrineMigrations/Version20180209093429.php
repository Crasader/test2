<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180209093429 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO auto_remit (id, removed, label, name) VALUES (4, 0, 'BBv2', 'BB自動認款2.0')");
        $this->addSql("INSERT INTO auto_remit_has_bank_info (auto_remit_id, bank_info_id) VALUES (4, 3)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM auto_remit_has_bank_info WHERE auto_remit_id = 4");
        $this->addSql("DELETE FROM auto_remit WHERE id = 4");
    }
}
