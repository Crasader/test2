<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170113102506 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE stat_cash_rebate ADD rebate_pt_slots_amount NUMERIC(16, 4) NOT NULL, ADD rebate_pt_slots_count INT NOT NULL');
        $this->addSql('ALTER TABLE stat_cash_rebate ADD rebate_pt_table_amount NUMERIC(16, 4) NOT NULL, ADD rebate_pt_table_count INT NOT NULL');
        $this->addSql('ALTER TABLE stat_cash_rebate ADD rebate_pt_jackpot_amount NUMERIC(16, 4) NOT NULL, ADD rebate_pt_jackpot_count INT NOT NULL');
        $this->addSql('ALTER TABLE stat_cash_rebate ADD rebate_pt_arcade_amount NUMERIC(16, 4) NOT NULL, ADD rebate_pt_arcade_count INT NOT NULL');
        $this->addSql('ALTER TABLE stat_cash_rebate ADD rebate_pt_scratch_amount NUMERIC(16, 4) NOT NULL, ADD rebate_pt_scratch_count INT NOT NULL');
        $this->addSql('ALTER TABLE stat_cash_rebate ADD rebate_pt_poker_amount NUMERIC(16, 4) NOT NULL, ADD rebate_pt_poker_count INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE stat_cash_rebate DROP rebate_pt_poker_amount, DROP rebate_pt_poker_count');
        $this->addSql('ALTER TABLE stat_cash_rebate DROP rebate_pt_scratch_amount, DROP rebate_pt_scratch_count');
        $this->addSql('ALTER TABLE stat_cash_rebate DROP rebate_pt_arcade_amount, DROP rebate_pt_arcade_count');
        $this->addSql('ALTER TABLE stat_cash_rebate DROP rebate_pt_jackpot_amount, DROP rebate_pt_jackpot_count');
        $this->addSql('ALTER TABLE stat_cash_rebate DROP rebate_pt_table_amount, DROP rebate_pt_table_count');
        $this->addSql('ALTER TABLE stat_cash_rebate DROP rebate_pt_slots_amount, DROP rebate_pt_slots_count');
    }
}
