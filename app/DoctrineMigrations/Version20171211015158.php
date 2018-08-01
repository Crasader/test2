<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171211015158 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE deposit_pay_status_error ADD deposit TINYINT(1) NOT NULL AFTER confirm_at, ADD card TINYINT(1) NOT NULL AFTER deposit, ADD remit TINYINT(1) NOT NULL AFTER card, ADD duplicate_error TINYINT(1) NOT NULL AFTER remit, ADD duplicate_count SMALLINT NOT NULL AFTER duplicate_error, ADD auto_remit_id SMALLINT UNSIGNED NOT NULL AFTER duplicate_count');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE deposit_pay_status_error DROP deposit, DROP card, DROP remit, DROP duplicate_error, DROP duplicate_count, DROP auto_remit_id');
    }
}
