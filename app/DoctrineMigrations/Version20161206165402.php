<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161206165402 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE stat_cash_rebate ADD rebate_bb_slots_amount NUMERIC(16, 4) NOT NULL, ADD rebate_bb_slots_count INT NOT NULL, ADD rebate_bb_table_amount NUMERIC(16, 4) NOT NULL, ADD rebate_bb_table_count INT NOT NULL, ADD rebate_bb_arcade_amount NUMERIC(16, 4) NOT NULL, ADD rebate_bb_arcade_count INT NOT NULL, ADD rebate_bb_scratch_amount NUMERIC(16, 4) NOT NULL, ADD rebate_bb_scratch_count INT NOT NULL, ADD rebate_bb_feature_amount NUMERIC(16, 4) NOT NULL, ADD rebate_bb_feature_count INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE stat_cash_rebate DROP rebate_bb_slots_amount, DROP rebate_bb_slots_count, DROP rebate_bb_table_amount, DROP rebate_bb_table_count, DROP rebate_bb_arcade_amount, DROP rebate_bb_arcade_count, DROP rebate_bb_scratch_amount, DROP rebate_bb_scratch_count, DROP rebate_bb_feature_amount, DROP rebate_bb_feature_count');
    }
}
