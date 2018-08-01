<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170406153314 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE share_update_record DROP FOREIGN KEY FK_8A2059D25FD14717');
        $this->addSql('DROP INDEX idx_8a2059d25fd14717 ON share_update_record');
        $this->addSql('CREATE INDEX IDX_CA3F59655FD14717 ON share_update_record (group_num)');
        $this->addSql('ALTER TABLE share_update_record ADD CONSTRAINT FK_8A2059D25FD14717 FOREIGN KEY (group_num) REFERENCES share_update_cron (group_num)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE share_update_record DROP FOREIGN KEY FK_8A2059D25FD14717');
        $this->addSql('DROP INDEX idx_ca3f59655fd14717 ON share_update_record');
        $this->addSql('CREATE INDEX IDX_8A2059D25FD14717 ON share_update_record (group_num)');
        $this->addSql('ALTER TABLE share_update_record ADD CONSTRAINT FK_8A2059D25FD14717 FOREIGN KEY (group_num) REFERENCES share_update_cron (group_num)');
    }
}
