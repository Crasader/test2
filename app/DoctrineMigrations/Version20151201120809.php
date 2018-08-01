<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151201120809 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE deposit_mobile (id INT UNSIGNED AUTO_INCREMENT NOT NULL, payment_charge_id INT UNSIGNED NOT NULL, discount SMALLINT UNSIGNED NOT NULL, discount_give_up TINYINT(1) NOT NULL, discount_amount NUMERIC(16, 4) NOT NULL, discount_percent NUMERIC(5, 2) NOT NULL, discount_factor SMALLINT UNSIGNED NOT NULL, discount_limit NUMERIC(16, 4) NOT NULL, deposit_max NUMERIC(16, 4) NOT NULL, deposit_min NUMERIC(16, 4) NOT NULL, audit_live TINYINT(1) NOT NULL, audit_live_amount NUMERIC(16, 4) NOT NULL, audit_ball TINYINT(1) NOT NULL, audit_ball_amount NUMERIC(16, 4) NOT NULL, audit_complex TINYINT(1) NOT NULL, audit_complex_amount NUMERIC(16, 4) NOT NULL, audit_normal TINYINT(1) NOT NULL, audit_normal_amount NUMERIC(5, 2) NOT NULL, audit_3d TINYINT(1) NOT NULL, audit_3d_amount NUMERIC(16, 4) NOT NULL, audit_battle TINYINT(1) NOT NULL, audit_battle_amount NUMERIC(16, 4) NOT NULL, audit_virtual TINYINT(1) NOT NULL, audit_virtual_amount NUMERIC(16, 4) NOT NULL, audit_discount_amount NUMERIC(5, 2) NOT NULL, audit_loosen NUMERIC(16, 4) NOT NULL, audit_administrative NUMERIC(5, 2) NOT NULL, version INT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_6DE3CF5E1601663C (payment_charge_id), PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE deposit_mobile ADD CONSTRAINT FK_6DE3CF5E1601663C FOREIGN KEY (payment_charge_id) REFERENCES payment_charge (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE deposit_mobile');
    }
}
