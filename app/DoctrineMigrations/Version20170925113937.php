<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170925113937 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("INSERT INTO auto_remit (id, removed, label, name) VALUES (1, 0, 'TongLueYun', '同略雲')");
        $this->addSql("INSERT INTO auto_remit_has_bank_info (auto_remit_id, bank_info_id) VALUES (1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 10), (1, 11), (1, 13), (1, 14), (1, 15), (1, 16)");
        $this->addSql("INSERT INTO domain_auto_remit (domain, auto_remit_id, enable, api_key) SELECT domain, 1, enable, api_key FROM auto_confirm_config");
        $this->addSql("UPDATE remit_account SET auto_remit_id = 1 WHERE auto_confirm = 1 and bb_auto_confirm = 0");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE remit_account SET auto_remit_id = 0 WHERE auto_remit_id = 1");
        $this->addSql("DELETE FROM domain_auto_remit WHERE auto_remit_id = 1");
        $this->addSql("DELETE FROM auto_remit_has_bank_info WHERE auto_remit_id = 1");
        $this->addSql("DELETE FROM auto_remit WHERE id = 1");
    }
}
