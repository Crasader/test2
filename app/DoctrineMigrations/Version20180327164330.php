<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180327164330 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE stat_cash_rebate ADD rebate_pt2_slots_amount NUMERIC(16, 4) NOT NULL, ADD rebate_pt2_slots_count INT NOT NULL, ADD rebate_pt2_jackpot_amount NUMERIC(16, 4) NOT NULL, ADD rebate_pt2_jackpot_count INT NOT NULL, ADD rebate_pt2_fishing_amount NUMERIC(16, 4) NOT NULL, ADD rebate_pt2_fishing_count INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE stat_cash_rebate DROP rebate_pt2_slots_amount, DROP rebate_pt2_slots_count, DROP rebate_pt2_jackpot_amount, DROP rebate_pt2_jackpot_count, DROP rebate_pt2_fishing_amount, DROP rebate_pt2_fishing_count');
    }
}
