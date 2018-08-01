<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170929055456 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("ALTER TABLE auto_confirm_entry ADD ref_id VARCHAR(64) DEFAULT NULL AFTER confirm_at");
        $this->addSql("CREATE INDEX idx_auto_confirm_entry_ref_id ON auto_confirm_entry (ref_id)");
        $this->addSql("INSERT INTO auto_remit (id, removed, label, name) VALUES (3, 0, 'MiaoFuTong','秒付通')");
        $this->addSql("INSERT INTO auto_remit_has_bank_info (auto_remit_id, bank_info_id) VALUES (3, 1), (3, 2), (3, 3), (3, 4), (3, 11), (3, 321)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM auto_remit_has_bank_info WHERE auto_remit_id = 3");
        $this->addSql("DELETE FROM auto_remit WHERE id = 3");
        $this->addSql("DROP INDEX idx_auto_confirm_entry_ref_id ON auto_confirm_entry");
        $this->addSql("ALTER TABLE auto_confirm_entry DROP ref_id");
    }
}
