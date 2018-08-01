<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160524145921 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE stat_cash_rebate ADD rebate_mg_jackpot_amount NUMERIC(16, 4) NOT NULL, ADD rebate_mg_jackpot_count INT NOT NULL, ADD rebate_mg_slots_amount NUMERIC(16, 4) NOT NULL, ADD rebate_mg_slots_count INT NOT NULL, ADD rebate_mg_feature_amount NUMERIC(16, 4) NOT NULL, ADD rebate_mg_feature_count INT NOT NULL, ADD rebate_mg_table_amount NUMERIC(16, 4) NOT NULL, ADD rebate_mg_table_count INT NOT NULL, ADD rebate_mg_mobile_amount NUMERIC(16, 4) NOT NULL, ADD rebate_mg_mobile_count INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE stat_cash_rebate DROP rebate_mg_jackpot_amount, DROP rebate_mg_jackpot_count, DROP rebate_mg_slots_amount, DROP rebate_mg_slots_count, DROP rebate_mg_feature_amount, DROP rebate_mg_feature_count, DROP rebate_mg_table_amount, DROP rebate_mg_table_count, DROP rebate_mg_mobile_amount, DROP rebate_mg_mobile_count');
    }
}
