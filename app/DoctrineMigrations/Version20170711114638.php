<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170711114638 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE remit_account ADD suspend TINYINT(1) NOT NULL AFTER enable');
        $this->addSql("INSERT INTO background_process (name, enable, begin_at, end_at, last_end_time, memo, num, msg_num) VALUES ('activate-remit-account', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '恢復公司入款帳號額度，每天 00:00', 0, 0)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM background_process WHERE name = 'activate-remit-account'");
        $this->addSql('ALTER TABLE remit_account DROP suspend');
    }
}
