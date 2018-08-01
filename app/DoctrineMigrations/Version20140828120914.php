<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140828120914 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        $this->addSql("ALTER TABLE remit_entry REMOVE PARTITIONING");
        $this->addSql("ALTER TABLE remit_entry DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE remit_entry ADD PRIMARY KEY(id)");
        $this->addSql("ALTER TABLE remit_entry CHANGE id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT");
        $this->addSql("CREATE INDEX idx_remit_entry_remit_account_id_created_at ON remit_entry (remit_account_id, created_at)");
        $this->addSql("CREATE INDEX idx_remit_entry_remit_account_id_confirm_at ON remit_entry (remit_account_id, confirm_at)");
        $this->addSql("CREATE INDEX idx_remit_account_domain ON remit_account (domain)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        $this->addSql("DROP INDEX idx_remit_account_domain ON remit_account");
        $this->addSql("DROP INDEX idx_remit_entry_remit_account_id_created_at ON remit_entry");
        $this->addSql("DROP INDEX idx_remit_entry_remit_account_id_confirm_at ON remit_entry");
        $this->addSql("ALTER TABLE remit_entry CHANGE id id INT(10) UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE remit_entry DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE remit_entry ADD PRIMARY KEY(id, created_at)");
        $this->addSql("ALTER TABLE remit_entry PARTITION BY RANGE (created_at) (PARTITION p201212 VALUES LESS THAN (20130101000000),  PARTITION p201301 VALUES LESS THAN (20130201000000), PARTITION p201302 VALUES LESS THAN (20130301000000), PARTITION p201303 VALUES LESS THAN (20130401000000), PARTITION p201304 VALUES LESS THAN (20130501000000), PARTITION p201305 VALUES LESS THAN (20130601000000), PARTITION p201306 VALUES LESS THAN (20130701000000), PARTITION p201307 VALUES LESS THAN (20130801000000), PARTITION p201308 VALUES LESS THAN (20130901000000), PARTITION p201309 VALUES LESS THAN (20131001000000), PARTITION p201310 VALUES LESS THAN (20131101000000), PARTITION p201311 VALUES LESS THAN (20131201000000), PARTITION p201312 VALUES LESS THAN (20140101000000), PARTITION p201401 VALUES LESS THAN (20140201000000), PARTITION p201402 VALUES LESS THAN (20140301000000), PARTITION p201403 VALUES LESS THAN (20140401000000), PARTITION p201404 VALUES LESS THAN (20140501000000), PARTITION p201405 VALUES LESS THAN (20140601000000), PARTITION p201406 VALUES LESS THAN (20140701000000), PARTITION p201407 VALUES LESS THAN (20140801000000), PARTITION p201408 VALUES LESS THAN (20140901000000), PARTITION p201409 VALUES LESS THAN (20141001000000), PARTITION p201410 VALUES LESS THAN (20141101000000), PARTITION p201411 VALUES LESS THAN (20141201000000), PARTITION p201412 VALUES LESS THAN (20150101000000), PARTITION p201501 VALUES LESS THAN (20150201000000), PARTITION p201502 VALUES LESS THAN (20150301000000), PARTITION p201503 VALUES LESS THAN (20150401000000), PARTITION p201504 VALUES LESS THAN (20150501000000), PARTITION p201505 VALUES LESS THAN (20150601000000), PARTITION p201506 VALUES LESS THAN (20150701000000), PARTITION p201507 VALUES LESS THAN (20150801000000), PARTITION p201508 VALUES LESS THAN (20150901000000), PARTITION p201509 VALUES LESS THAN (20151001000000), PARTITION p201510 VALUES LESS THAN (20151101000000), PARTITION p999999 VALUES LESS THAN MAXVALUE)");
    }
}
