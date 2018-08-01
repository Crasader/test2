<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140830115922 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE stat_cash_user ".
            "DROP offer_forfeit_deposit_amount, DROP offer_forfeit_deposit_count,".
            "ADD deposit_withdraw_amount NUMERIC(16, 4) NOT NULL AFTER currency, ADD deposit_withdraw_count INT UNSIGNED NOT NULL AFTER deposit_withdraw_amount," .
            "ADD offer_rebate_remit_amount NUMERIC(16, 4) NOT NULL AFTER withdraw_count, ADD offer_rebate_remit_count INT UNSIGNED NOT NULL AFTER offer_rebate_remit_amount," .
            "ADD rebate_amount NUMERIC(16, 4) NOT NULL AFTER offer_active_count, ADD rebate_count INT UNSIGNED NOT NULL AFTER rebate_amount,".
            "ADD rebate_agvideo_amount NUMERIC(16, 4) NOT NULL AFTER rebate_vipvideo_count, ADD rebate_agvideo_count INT UNSIGNED NOT NULL AFTER rebate_agvideo_amount,".
            "ADD remit_amount NUMERIC(16, 4) NOT NULL AFTER rebate_agvideo_count, ADD remit_count INT UNSIGNED NOT NULL AFTER remit_amount,".
            "CHANGE offer_remit_amount offer_remit_amount DECIMAL(16, 4) NOT NULL AFTER remit_count,".
            "CHANGE offer_remit_count offer_remit_count INT(10) UNSIGNED NOT NULL AFTER offer_remit_amount,".
            "CHANGE offer_company_remit_amount offer_company_remit_amount DECIMAL(16, 4) NOT NULL AFTER offer_remit_count,".
            "CHANGE offer_company_remit_count offer_company_remit_count INT(10) UNSIGNED NOT NULL AFTER offer_company_remit_amount");
        $this->addSql("ALTER TABLE stat_cash_user DROP INDEX idx_stat_cash_user_at");
        $this->addSql("DROP INDEX uni_at_user_id ON stat_cash_user");
        $this->addSql("CREATE UNIQUE INDEX uni_stat_cash_user_at_user_id ON stat_cash_user (at, user_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE stat_cash_user ".
            "DROP deposit_withdraw_amount, DROP deposit_withdraw_count,".
            "DROP offer_rebate_remit_amount, DROP offer_rebate_remit_count," .
            "DROP rebate_amount, DROP rebate_count,".
            "DROP rebate_agvideo_amount, DROP rebate_agvideo_count,".
            "DROP remit_amount, DROP remit_count,".
            "CHANGE offer_remit_amount offer_remit_amount DECIMAL(16, 4) NOT NULL AFTER offer_deposit_count,".
            "CHANGE offer_remit_count offer_remit_count INT(10) UNSIGNED NOT NULL AFTER offer_remit_amount,".
            "CHANGE offer_company_remit_amount offer_company_remit_amount DECIMAL(16, 4) NOT NULL AFTER offer_company_deposit_count,".
            "CHANGE offer_company_remit_count offer_company_remit_count INT(10) UNSIGNED NOT NULL AFTER offer_company_remit_amount,".
            "ADD offer_forfeit_deposit_amount NUMERIC(16, 4) NOT NULL AFTER offer_remit_count, ADD offer_forfeit_deposit_count INT(10) UNSIGNED NOT NULL AFTER offer_forfeit_deposit_amount");
        $this->addSql("ALTER TABLE stat_cash_user ADD INDEX idx_stat_cash_user_at (at)");
        $this->addSql("DROP INDEX uni_stat_cash_user_at_user_id ON stat_cash_user");
        $this->addSql("CREATE UNIQUE INDEX uni_at_user_id ON stat_cash_user (at, user_id)");
    }
}
