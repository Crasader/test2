<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171108070522 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bitcoin_withdraw_entry CHANGE aduit_fee audit_fee NUMERIC(16, 4) NOT NULL');
        $this->addSql('ALTER TABLE bitcoin_withdraw_entry CHANGE aduit_charge audit_charge NUMERIC(16, 4) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bitcoin_withdraw_entry CHANGE audit_fee aduit_fee NUMERIC(16, 4) NOT NULL');
        $this->addSql('ALTER TABLE bitcoin_withdraw_entry CHANGE audit_charge aduit_charge NUMERIC(16, 4) NOT NULL');
    }
}
