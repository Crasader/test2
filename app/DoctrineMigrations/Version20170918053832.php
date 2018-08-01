<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170918053832 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE stat_cash_rebate ADD rebate_pp_slots_amount NUMERIC(16, 4) NOT NULL, ADD rebate_pp_slots_count INT NOT NULL, ADD rebate_pp_table_amount NUMERIC(16, 4) NOT NULL, ADD rebate_pp_table_count INT NOT NULL, ADD rebate_pp_jackpot_amount NUMERIC(16, 4) NOT NULL, ADD rebate_pp_jackpot_count INT NOT NULL, ADD rebate_pp_feature_amount NUMERIC(16, 4) NOT NULL, ADD rebate_pp_feature_count INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE stat_cash_rebate DROP rebate_pp_slots_amount, DROP rebate_pp_slots_count, DROP rebate_pp_table_amount, DROP rebate_pp_table_count, DROP rebate_pp_jackpot_amount, DROP rebate_pp_jackpot_count, DROP rebate_pp_feature_amount, DROP rebate_pp_feature_count');
    }
}
