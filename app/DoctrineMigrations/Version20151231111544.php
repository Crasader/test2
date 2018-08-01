<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151231111544 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payment_withdraw_fee ADD mobile_free_period SMALLINT NOT NULL, ADD mobile_free_count SMALLINT NOT NULL, ADD mobile_amount_max NUMERIC(16, 4) NOT NULL, ADD mobile_amount_percent NUMERIC(5, 2) NOT NULL, ADD mobile_withdraw_max NUMERIC(16, 4) NOT NULL, ADD mobile_withdraw_min NUMERIC(16, 4) NOT NULL');
        $this->addSql('UPDATE payment_withdraw_fee SET mobile_free_period = free_period, mobile_free_count = free_count, mobile_amount_max = amount_max, mobile_amount_percent = amount_percent, mobile_withdraw_max = withdraw_max, mobile_withdraw_min = withdraw_min');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payment_withdraw_fee DROP mobile_free_period, DROP mobile_free_count, DROP mobile_amount_max, DROP mobile_amount_percent, DROP mobile_withdraw_max, DROP mobile_withdraw_min');
    }
}
