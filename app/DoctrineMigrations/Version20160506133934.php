<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160506133934 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE deposit_tracking ADD merchant_id INT UNSIGNED NOT NULL AFTER payment_gateway_id');
        $this->addSql('UPDATE deposit_tracking t SET merchant_id = (SELECT merchant_id FROM cash_deposit_entry d WHERE t.entry_id = d.id)');
        $this->addSql('ALTER TABLE card_deposit_tracking ADD merchant_card_id INT UNSIGNED NOT NULL AFTER payment_gateway_id');
        $this->addSql('UPDATE card_deposit_tracking t SET merchant_card_id = (SELECT merchant_card_id FROM card_deposit_entry d WHERE t.entry_id = d.id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE deposit_tracking DROP merchant_id');
        $this->addSql('ALTER TABLE card_deposit_tracking DROP merchant_card_id');
    }
}
