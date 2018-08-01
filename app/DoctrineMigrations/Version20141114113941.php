<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141114113941 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("CREATE TABLE stat_cash_offer (id INT UNSIGNED AUTO_INCREMENT NOT NULL, at DATETIME NOT NULL, user_id INT NOT NULL, currency SMALLINT UNSIGNED NOT NULL, domain INT NOT NULL, parent_id INT NOT NULL, offer_deposit_amount NUMERIC(16, 4) NOT NULL, offer_deposit_count INT NOT NULL, offer_back_commission_amount NUMERIC(16, 4) NOT NULL, offer_back_commission_count INT NOT NULL, offer_company_deposit_amount NUMERIC(16, 4) NOT NULL, offer_company_deposit_count INT NOT NULL, offer_online_deposit_amount NUMERIC(16, 4) NOT NULL, offer_online_deposit_count INT NOT NULL, offer_active_amount NUMERIC(16, 4) NOT NULL, offer_active_count INT NOT NULL, offer_amount NUMERIC(16, 4) NOT NULL, offer_count INT NOT NULL, version INT UNSIGNED NOT NULL, INDEX idx_stat_cash_offer_at_user_id (at, user_id), INDEX idx_stat_cash_offer_domain_at (domain, at), PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE stat_cash_rebate (id INT UNSIGNED AUTO_INCREMENT NOT NULL, at DATETIME NOT NULL, user_id INT NOT NULL, currency SMALLINT UNSIGNED NOT NULL, domain INT NOT NULL, parent_id INT NOT NULL, rebate_ball_amount NUMERIC(16, 4) NOT NULL, rebate_ball_count INT NOT NULL, rebate_keno_amount NUMERIC(16, 4) NOT NULL, rebate_keno_count INT NOT NULL, rebate_video_amount NUMERIC(16, 4) NOT NULL, rebate_video_count INT NOT NULL, rebate_sport_amount NUMERIC(16, 4) NOT NULL, rebate_sport_count INT NOT NULL, rebate_prob_amount NUMERIC(16, 4) NOT NULL, rebate_prob_count INT NOT NULL, rebate_lottery_amount NUMERIC(16, 4) NOT NULL, rebate_lottery_count INT NOT NULL, rebate_bbplay_amount NUMERIC(16, 4) NOT NULL, rebate_bbplay_count INT NOT NULL, rebate_offer_amount NUMERIC(16, 4) NOT NULL, rebate_offer_count INT NOT NULL, rebate_bbvideo_amount NUMERIC(16, 4) NOT NULL, rebate_bbvideo_count INT NOT NULL, rebate_ttvideo_amount NUMERIC(16, 4) NOT NULL, rebate_ttvideo_count INT NOT NULL, rebate_armvideo_amount NUMERIC(16, 4) NOT NULL, rebate_armvideo_count INT NOT NULL, rebate_xpjvideo_amount NUMERIC(16, 4) NOT NULL, rebate_xpjvideo_count INT NOT NULL, rebate_yfvideo_amount NUMERIC(16, 4) NOT NULL, rebate_yfvideo_count INT NOT NULL, rebate_3d_amount NUMERIC(16, 4) NOT NULL, rebate_3d_count INT NOT NULL, rebate_battle_amount NUMERIC(16, 4) NOT NULL, rebate_battle_count INT NOT NULL, rebate_virtual_amount NUMERIC(16, 4) NOT NULL, rebate_virtual_count INT NOT NULL, rebate_vipvideo_amount NUMERIC(16, 4) NOT NULL, rebate_vipvideo_count INT NOT NULL, rebate_agvideo_amount NUMERIC(16, 4) NOT NULL, rebate_agvideo_count INT NOT NULL, rebate_amount NUMERIC(16, 4) NOT NULL, rebate_count INT NOT NULL, version INT UNSIGNED NOT NULL, INDEX idx_stat_cash_rebate_at_user_id (at, user_id), INDEX idx_stat_cash_rebate_domain_at (domain, at), PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE stat_cash_remit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, at DATETIME NOT NULL, user_id INT NOT NULL, currency SMALLINT UNSIGNED NOT NULL, domain INT NOT NULL, parent_id INT NOT NULL, offer_remit_amount NUMERIC(16, 4) NOT NULL, offer_remit_count INT NOT NULL, offer_company_remit_amount NUMERIC(16, 4) NOT NULL, offer_company_remit_count INT NOT NULL, remit_amount NUMERIC(16, 4) NOT NULL, remit_count INT NOT NULL, version INT UNSIGNED NOT NULL, INDEX idx_stat_cash_remit_at_user_id (at, user_id), INDEX idx_stat_cash_remit_domain_at (domain, at), PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE stat_cash_all_offer (id INT UNSIGNED AUTO_INCREMENT NOT NULL, at DATETIME NOT NULL, user_id INT NOT NULL, currency SMALLINT UNSIGNED NOT NULL, domain INT NOT NULL, parent_id INT NOT NULL, offer_rebate_remit_amount NUMERIC(16, 4) NOT NULL, offer_rebate_remit_count INT NOT NULL, version INT UNSIGNED NOT NULL, INDEX idx_stat_cash_all_offer_at_user_id (at, user_id), INDEX idx_stat_cash_all_offer_domain_at (domain, at), PRIMARY KEY(id))");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE stat_cash_offer");
        $this->addSql("DROP TABLE stat_cash_rebate");
        $this->addSql("DROP TABLE stat_cash_remit");
        $this->addSql("DROP TABLE stat_cash_all_offer");
    }
}
