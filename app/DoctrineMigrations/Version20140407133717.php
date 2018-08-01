<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140407133717 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE stat_cash_user ADD currency SMALLINT UNSIGNED NOT NULL AFTER domain, ADD offer_deposit_amount NUMERIC(16, 4) NOT NULL, ADD offer_deposit_count INT UNSIGNED NOT NULL, ADD offer_remit_amount NUMERIC(16, 4) NOT NULL, ADD offer_remit_count INT UNSIGNED NOT NULL, ADD offer_forfeit_deposit_amount NUMERIC(16, 4) NOT NULL, ADD offer_forfeit_deposit_count INT UNSIGNED NOT NULL, ADD offer_back_commission_amount NUMERIC(16, 4) NOT NULL, ADD offer_back_commission_count INT UNSIGNED NOT NULL, ADD offer_company_deposit_amount NUMERIC(16, 4) NOT NULL, ADD offer_company_deposit_count INT UNSIGNED NOT NULL, ADD offer_company_remit_amount NUMERIC(16, 4) NOT NULL, ADD offer_company_remit_count INT UNSIGNED NOT NULL, ADD offer_online_deposit_amount NUMERIC(16, 4) NOT NULL, ADD offer_online_deposit_count INT UNSIGNED NOT NULL, ADD offer_active_amount NUMERIC(16, 4) NOT NULL, ADD offer_active_count INT UNSIGNED NOT NULL, ADD rebate_ball_amount NUMERIC(16, 4) NOT NULL, ADD rebate_ball_count INT UNSIGNED NOT NULL, ADD rebate_keno_amount NUMERIC(16, 4) NOT NULL, ADD rebate_keno_count INT UNSIGNED NOT NULL, ADD rebate_video_amount NUMERIC(16, 4) NOT NULL, ADD rebate_video_count INT UNSIGNED NOT NULL, ADD rebate_sport_amount NUMERIC(16, 4) NOT NULL, ADD rebate_sport_count INT UNSIGNED NOT NULL, ADD rebate_prob_amount NUMERIC(16, 4) NOT NULL, ADD rebate_prob_count INT UNSIGNED NOT NULL, ADD rebate_lottery_amount NUMERIC(16, 4) NOT NULL, ADD rebate_lottery_count INT UNSIGNED NOT NULL, ADD rebate_bbplay_amount NUMERIC(16, 4) NOT NULL, ADD rebate_bbplay_count INT UNSIGNED NOT NULL, ADD rebate_offer_amount NUMERIC(16, 4) NOT NULL, ADD rebate_offer_count INT UNSIGNED NOT NULL, ADD rebate_bbvideo_amount NUMERIC(16, 4) NOT NULL, ADD rebate_bbvideo_count INT UNSIGNED NOT NULL, ADD rebate_ttvideo_amount NUMERIC(16, 4) NOT NULL, ADD rebate_ttvideo_count INT UNSIGNED NOT NULL, ADD rebate_armvideo_amount NUMERIC(16, 4) NOT NULL, ADD rebate_armvideo_count INT UNSIGNED NOT NULL, ADD rebate_xpjvideo_amount NUMERIC(16, 4) NOT NULL, ADD rebate_xpjvideo_count INT UNSIGNED NOT NULL, ADD rebate_yfvideo_amount NUMERIC(16, 4) NOT NULL, ADD rebate_yfvideo_count INT UNSIGNED NOT NULL, ADD rebate_3d_amount NUMERIC(16, 4) NOT NULL, ADD rebate_3d_count INT UNSIGNED NOT NULL, ADD rebate_battle_amount NUMERIC(16, 4) NOT NULL, ADD rebate_battle_count INT UNSIGNED NOT NULL, ADD rebate_virtual_amount NUMERIC(16, 4) NOT NULL, ADD rebate_virtual_count INT UNSIGNED NOT NULL, ADD rebate_vipvideo_amount NUMERIC(16, 4) NOT NULL, ADD rebate_vipvideo_count INT UNSIGNED NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE stat_cash_user DROP currency, DROP offer_deposit_amount, DROP offer_deposit_count, DROP offer_remit_amount, DROP offer_remit_count, DROP offer_forfeit_deposit_amount, DROP offer_forfeit_deposit_count, DROP offer_back_commission_amount, DROP offer_back_commission_count, DROP offer_company_deposit_amount, DROP offer_company_deposit_count, DROP offer_company_remit_amount, DROP offer_company_remit_count, DROP offer_online_deposit_amount, DROP offer_online_deposit_count, DROP offer_active_amount, DROP offer_active_count, DROP rebate_ball_amount, DROP rebate_ball_count, DROP rebate_keno_amount, DROP rebate_keno_count, DROP rebate_video_amount, DROP rebate_video_count, DROP rebate_sport_amount, DROP rebate_sport_count, DROP rebate_prob_amount, DROP rebate_prob_count, DROP rebate_lottery_amount, DROP rebate_lottery_count, DROP rebate_bbplay_amount, DROP rebate_bbplay_count, DROP rebate_offer_amount, DROP rebate_offer_count, DROP rebate_bbvideo_amount, DROP rebate_bbvideo_count, DROP rebate_ttvideo_amount, DROP rebate_ttvideo_count, DROP rebate_armvideo_amount, DROP rebate_armvideo_count, DROP rebate_xpjvideo_amount, DROP rebate_xpjvideo_count, DROP rebate_yfvideo_amount, DROP rebate_yfvideo_count, DROP rebate_3d_amount, DROP rebate_3d_count, DROP rebate_battle_amount, DROP rebate_battle_count, DROP rebate_virtual_amount, DROP rebate_virtual_count, DROP rebate_vipvideo_amount, DROP rebate_vipvideo_count");
    }
}
