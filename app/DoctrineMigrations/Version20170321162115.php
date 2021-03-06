<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170321162115 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE cash_fake_transfer_entry_new (id BIGINT NOT NULL, at BIGINT NOT NULL, user_id INT NOT NULL, domain INT NOT NULL, currency SMALLINT NOT NULL, opcode INT NOT NULL, created_at DATETIME NOT NULL, amount NUMERIC(16, 4) NOT NULL, balance NUMERIC(16, 4) NOT NULL, ref_id BIGINT DEFAULT 0 NOT NULL, memo VARCHAR(100) DEFAULT "" NOT NULL, INDEX idx_cash_fake_transfer_entry_at (at), INDEX idx_cash_fake_transfer_entry_ref_id (ref_id), INDEX idx_cash_fake_transfer_entry_user_id_at (user_id, at), INDEX idx_cash_fake_transfer_entry_domain_at (domain, at), PRIMARY KEY(id, at)) PARTITION BY RANGE (at) (PARTITION p201306 VALUES LESS THAN (20130701000000), PARTITION p201307 VALUES LESS THAN (20130801000000), PARTITION p201308 VALUES LESS THAN (20130901000000), PARTITION p201309 VALUES LESS THAN (20131001000000), PARTITION p201310 VALUES LESS THAN (20131101000000), PARTITION p201311 VALUES LESS THAN (20131201000000), PARTITION p201312 VALUES LESS THAN (20140101000000), PARTITION p201401 VALUES LESS THAN (20140201000000), PARTITION p201402 VALUES LESS THAN (20140301000000), PARTITION p201403 VALUES LESS THAN (20140401000000), PARTITION p201404 VALUES LESS THAN (20140501000000), PARTITION p201405 VALUES LESS THAN (20140601000000), PARTITION p201406 VALUES LESS THAN (20140701000000), PARTITION p201407 VALUES LESS THAN (20140801000000), PARTITION p201408 VALUES LESS THAN (20140901000000), PARTITION p201409 VALUES LESS THAN (20141001000000), PARTITION p201410 VALUES LESS THAN (20141101000000), PARTITION p201411 VALUES LESS THAN (20141201000000), PARTITION p201412 VALUES LESS THAN (20150101000000), PARTITION p201501 VALUES LESS THAN (20150201000000), PARTITION p201502 VALUES LESS THAN (20150301000000), PARTITION p201503 VALUES LESS THAN (20150401000000), PARTITION p201504 VALUES LESS THAN (20150501000000), PARTITION p201505 VALUES LESS THAN (20150601000000), PARTITION p201506 VALUES LESS THAN (20150701000000), PARTITION p201507 VALUES LESS THAN (20150801000000), PARTITION p201508 VALUES LESS THAN (20150901000000), PARTITION p201509 VALUES LESS THAN (20151001000000), PARTITION p201510 VALUES LESS THAN (20151101000000), PARTITION p201511 VALUES LESS THAN (20151201000000), PARTITION p201512 VALUES LESS THAN (20160101000000), PARTITION p201601 VALUES LESS THAN (20160201000000), PARTITION p201602 VALUES LESS THAN (20160301000000), PARTITION p201603 VALUES LESS THAN (20160401000000), PARTITION p201604 VALUES LESS THAN (20160501000000), PARTITION p201605 VALUES LESS THAN (20160601000000), PARTITION p201606 VALUES LESS THAN (20160701000000), PARTITION p201607 VALUES LESS THAN (20160801000000), PARTITION p201608 VALUES LESS THAN (20160901000000), PARTITION p201609 VALUES LESS THAN (20161001000000), PARTITION p201610 VALUES LESS THAN (20161101000000), PARTITION p201611 VALUES LESS THAN (20161201000000), PARTITION p201612 VALUES LESS THAN (20170101000000), PARTITION p201701 VALUES LESS THAN (20170201000000), PARTITION p201702 VALUES LESS THAN (20170301000000), PARTITION p201703 VALUES LESS THAN (20170401000000), PARTITION p201704 VALUES LESS THAN (20170501000000), PARTITION p201705 VALUES LESS THAN (20170601000000), PARTITION p201706 VALUES LESS THAN (20170701000000), PARTITION p201707 VALUES LESS THAN (20170801000000), PARTITION p201708 VALUES LESS THAN (20170901000000), PARTITION p201709 VALUES LESS THAN (20171001000000), PARTITION p201710 VALUES LESS THAN (20171101000000), PARTITION p201711 VALUES LESS THAN (20171201000000), PARTITION P999999 VALUES LESS THAN MAXVALUE);');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE cash_fake_transfer_entry_new');
    }
}
