<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170301080317 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("ALTER TABLE remit_account CHANGE id id INT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE remit_account_level CHANGE remit_account_id remit_account_id INT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE remit_account_qrcode CHANGE remit_account_id remit_account_id INT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE remit_entry CHANGE remit_account_id remit_account_id INT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE transcribe_entry CHANGE remit_account_id remit_account_id INT UNSIGNED NOT NULL");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("ALTER TABLE transcribe_entry CHANGE remit_account_id remit_account_id SMALLINT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE remit_entry CHANGE remit_account_id remit_account_id SMALLINT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE remit_account_qrcode CHANGE remit_account_id remit_account_id SMALLINT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE remit_account_level CHANGE remit_account_id remit_account_id SMALLINT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE remit_account CHANGE id id SMALLINT UNSIGNED NOT NULL");
    }
}
