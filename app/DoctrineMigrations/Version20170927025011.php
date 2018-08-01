<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170927025011 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO auto_remit (id, removed, label, name) VALUES (2, 0, 'BB', 'BB自動認款')");
        $this->addSql("INSERT INTO auto_remit_has_bank_info (auto_remit_id, bank_info_id) VALUE (2, 2), (2, 4)");
        $this->addSql("INSERT INTO domain_auto_remit (domain, auto_remit_id, enable, api_key) SELECT domain, 2, bb_enable, '' FROM auto_confirm_config");
        $this->addSql("UPDATE remit_account SET auto_remit_id = 2 WHERE auto_confirm = 1 and bb_auto_confirm = 1");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE remit_account SET auto_remit_id = 0 WHERE auto_remit_id = 2");
        $this->addSql("DELETE FROM domain_auto_remit WHERE auto_remit_id = 2");
        $this->addSql("DELETE FROM auto_remit_has_bank_info WHERE auto_remit_id = 2");
        $this->addSql("DELETE FROM auto_remit WHERE id = 2");
    }
}
