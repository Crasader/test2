<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170509133235 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE stat_cash_rebate ADD rebate_sk_1_amount NUMERIC(16, 4) NOT NULL, ADD rebate_sk_1_count INT NOT NULL, ADD rebate_sk_2_amount NUMERIC(16, 4) NOT NULL, ADD rebate_sk_2_count INT NOT NULL, ADD rebate_sk_3_amount NUMERIC(16, 4) NOT NULL, ADD rebate_sk_3_count INT NOT NULL, ADD rebate_sk_4_amount NUMERIC(16, 4) NOT NULL, ADD rebate_sk_4_count INT NOT NULL, ADD rebate_sk_5_amount NUMERIC(16, 4) NOT NULL, ADD rebate_sk_5_count INT NOT NULL, ADD rebate_sk_6_amount NUMERIC(16, 4) NOT NULL, ADD rebate_sk_6_count INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE stat_cash_rebate DROP rebate_sk_1_amount, DROP rebate_sk_1_count, DROP rebate_sk_2_amount, DROP rebate_sk_2_count, DROP rebate_sk_3_amount, DROP rebate_sk_3_count, DROP rebate_sk_4_amount, DROP rebate_sk_4_count, DROP rebate_sk_5_amount, DROP rebate_sk_5_count, DROP rebate_sk_6_amount, DROP rebate_sk_6_count');
    }
}
