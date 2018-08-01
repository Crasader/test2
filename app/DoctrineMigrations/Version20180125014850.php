<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180125014850 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE stat_cash_rebate ADD rebate_vr_vr_amount NUMERIC(16, 4) NOT NULL, ADD rebate_vr_vr_count INT NOT NULL, ADD rebate_vr_lotto_amount NUMERIC(16, 4) NOT NULL, ADD rebate_vr_lotto_count INT NOT NULL, ADD rebate_vr_marksix_amount NUMERIC(16, 4) NOT NULL, ADD rebate_vr_marksix_count INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE stat_cash_rebate DROP rebate_vr_vr_amount, DROP rebate_vr_vr_count, DROP rebate_vr_lotto_amount, DROP rebate_vr_lotto_count, DROP rebate_vr_marksix_amount, DROP rebate_vr_marksix_count');
    }
}
