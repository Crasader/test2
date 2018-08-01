<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130722111011 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE cash_fake_transfer_entry ADD user_id INT NOT NULL AFTER cash_fake_id, ADD currency SMALLINT NOT NULL AFTER user_id");

        $this->addSql("UPDATE cash_fake_transfer_entry AS cfte, cash_fake AS cf SET cfte.currency=(CASE cf.currency WHEN 'CNY' THEN 156 WHEN 'EUR' THEN 978 WHEN 'GBP' THEN 826 WHEN 'HKD' THEN 344 WHEN 'IDR' THEN 360 WHEN 'JPY' THEN 392 WHEN 'KRW' THEN 410 WHEN 'MYR' THEN 458 WHEN 'SGD' THEN 702 WHEN 'THB' THEN 764 WHEN 'TWD' THEN 901 WHEN 'USD' THEN 840 WHEN 'VND' THEN 704 END), cfte.user_id = cf.user_id WHERE cfte.cash_fake_id = cf.id");
        $this->addSql("CREATE INDEX idx_cash_fake_transfer_entry_user_id_at ON cash_fake_transfer_entry (user_id, at)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX idx_cash_fake_transfer_entry_user_id_at ON cash_fake_transfer_entry");
        $this->addSql("ALTER TABLE cash_fake_transfer_entry DROP user_id, DROP currency");
    }
}
