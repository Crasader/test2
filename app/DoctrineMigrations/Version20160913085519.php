<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160913085519 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE stat_cash_rebate ADD rebate_bbfg_amount NUMERIC(16, 4) NOT NULL, ADD rebate_bbfg_count INT NOT NULL');
        $this->addSql("INSERT INTO `maintain` (`code`, `begin_at`, `end_at`, `modified_at`, `msg`, `operator`) VALUES (30, '2000-01-01 00:00:00', '2000-01-01 00:00:00', '2000-01-01 00:00:00', '', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE stat_cash_rebate DROP rebate_bbfg_amount, DROP rebate_bbfg_count');
        $this->addSql("DELETE FROM `maintain` WHERE `code` = 30");
    }
}
