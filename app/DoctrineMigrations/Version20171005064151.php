<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171005064151 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE stat_cash_rebate ADD rebate_pt_fishing_amount NUMERIC(16, 4) NOT NULL, ADD rebate_pt_fishing_count INT NOT NULL');
        $this->addSql('ALTER TABLE stat_cash_rebate ADD rebate_gns_slots_amount NUMERIC(16, 4) NOT NULL, ADD rebate_gns_slots_count INT NOT NULL');
        $this->addSql('ALTER TABLE stat_cash_rebate ADD rebate_gns_fishing_amount NUMERIC(16, 4) NOT NULL, ADD rebate_gns_fishing_count INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE stat_cash_rebate DROP rebate_pt_fishing_amount, DROP rebate_pt_fishing_count');
        $this->addSql('ALTER TABLE stat_cash_rebate DROP rebate_gns_slots_amount, DROP rebate_gns_slots_count');
        $this->addSql('ALTER TABLE stat_cash_rebate DROP rebate_gns_fishing_amount, DROP rebate_gns_fishing_count');
    }
}
