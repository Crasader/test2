<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130716071642 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE cash_transfer_entry ADD user_id INT NOT NULL AFTER cash_id, ADD currency SMALLINT NOT NULL AFTER user_id");
        $this->addSql("UPDATE cash_transfer_entry AS cte, cash AS c set cte.currency=(case c.currency when 'CNY' then 156 when 'EUR' then 978 when 'GBP' then 826 when 'HKD' then 344 when 'IDR' then 360 when 'JPY' then 392 when 'KRW' then 410 when 'MYR' then 458 when 'SGD' then 702 when 'THB' then 764 when 'TWD' then 901 when 'USD' then 840 when 'VND' then 704 end), cte.user_id = c.user_id WHERE cte.cash_id = c.id");

        $this->addSql("CREATE INDEX idx_cash_transfer_entry_user_id_at ON cash_transfer_entry (user_id, at)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX idx_cash_transfer_entry_user_id_at ON cash_transfer_entry");
        $this->addSql("ALTER TABLE cash_transfer_entry DROP user_id, DROP currency");
    }
}
