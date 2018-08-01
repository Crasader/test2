<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140919080635 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE stat_cash_user CHANGE deposit_withdraw_count deposit_withdraw_count INT NOT NULL, CHANGE deposit_count deposit_count INT NOT NULL, CHANGE withdraw_count withdraw_count INT NOT NULL, CHANGE offer_rebate_remit_count offer_rebate_remit_count INT NOT NULL, CHANGE offer_count offer_count INT NOT NULL, CHANGE offer_deposit_count offer_deposit_count INT NOT NULL, CHANGE offer_back_commission_count offer_back_commission_count INT NOT NULL, CHANGE offer_company_deposit_count offer_company_deposit_count INT NOT NULL, CHANGE offer_online_deposit_count offer_online_deposit_count INT NOT NULL, CHANGE offer_active_count offer_active_count INT NOT NULL, CHANGE rebate_count rebate_count INT NOT NULL, CHANGE rebate_ball_count rebate_ball_count INT NOT NULL, CHANGE rebate_keno_count rebate_keno_count INT NOT NULL, CHANGE rebate_video_count rebate_video_count INT NOT NULL, CHANGE rebate_sport_count rebate_sport_count INT NOT NULL, CHANGE rebate_prob_count rebate_prob_count INT NOT NULL, CHANGE rebate_lottery_count rebate_lottery_count INT NOT NULL, CHANGE rebate_bbplay_count rebate_bbplay_count INT NOT NULL, CHANGE rebate_offer_count rebate_offer_count INT NOT NULL, CHANGE rebate_bbvideo_count rebate_bbvideo_count INT NOT NULL, CHANGE rebate_ttvideo_count rebate_ttvideo_count INT NOT NULL, CHANGE rebate_armvideo_count rebate_armvideo_count INT NOT NULL, CHANGE rebate_xpjvideo_count rebate_xpjvideo_count INT NOT NULL, CHANGE rebate_yfvideo_count rebate_yfvideo_count INT NOT NULL, CHANGE rebate_3d_count rebate_3d_count INT NOT NULL, CHANGE rebate_battle_count rebate_battle_count INT NOT NULL, CHANGE rebate_virtual_count rebate_virtual_count INT NOT NULL, CHANGE rebate_vipvideo_count rebate_vipvideo_count INT NOT NULL, CHANGE rebate_agvideo_count rebate_agvideo_count INT NOT NULL, CHANGE remit_count remit_count INT NOT NULL, CHANGE offer_remit_count offer_remit_count INT NOT NULL, CHANGE offer_company_remit_count offer_company_remit_count INT NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE stat_cash_user CHANGE deposit_withdraw_count deposit_withdraw_count INT UNSIGNED NOT NULL, CHANGE deposit_count deposit_count INT UNSIGNED NOT NULL, CHANGE withdraw_count withdraw_count INT UNSIGNED NOT NULL, CHANGE offer_rebate_remit_count offer_rebate_remit_count INT UNSIGNED NOT NULL, CHANGE offer_count offer_count INT UNSIGNED NOT NULL, CHANGE offer_deposit_count offer_deposit_count INT UNSIGNED NOT NULL, CHANGE offer_back_commission_count offer_back_commission_count INT UNSIGNED NOT NULL, CHANGE offer_company_deposit_count offer_company_deposit_count INT UNSIGNED NOT NULL, CHANGE offer_online_deposit_count offer_online_deposit_count INT UNSIGNED NOT NULL, CHANGE offer_active_count offer_active_count INT UNSIGNED NOT NULL, CHANGE rebate_count rebate_count INT UNSIGNED NOT NULL, CHANGE rebate_ball_count rebate_ball_count INT UNSIGNED NOT NULL, CHANGE rebate_keno_count rebate_keno_count INT UNSIGNED NOT NULL, CHANGE rebate_video_count rebate_video_count INT UNSIGNED NOT NULL, CHANGE rebate_sport_count rebate_sport_count INT UNSIGNED NOT NULL, CHANGE rebate_prob_count rebate_prob_count INT UNSIGNED NOT NULL, CHANGE rebate_lottery_count rebate_lottery_count INT UNSIGNED NOT NULL, CHANGE rebate_bbplay_count rebate_bbplay_count INT UNSIGNED NOT NULL, CHANGE rebate_offer_count rebate_offer_count INT UNSIGNED NOT NULL, CHANGE rebate_bbvideo_count rebate_bbvideo_count INT UNSIGNED NOT NULL, CHANGE rebate_ttvideo_count rebate_ttvideo_count INT UNSIGNED NOT NULL, CHANGE rebate_armvideo_count rebate_armvideo_count INT UNSIGNED NOT NULL, CHANGE rebate_xpjvideo_count rebate_xpjvideo_count INT UNSIGNED NOT NULL, CHANGE rebate_yfvideo_count rebate_yfvideo_count INT UNSIGNED NOT NULL, CHANGE rebate_3d_count rebate_3d_count INT UNSIGNED NOT NULL, CHANGE rebate_battle_count rebate_battle_count INT UNSIGNED NOT NULL, CHANGE rebate_virtual_count rebate_virtual_count INT UNSIGNED NOT NULL, CHANGE rebate_vipvideo_count rebate_vipvideo_count INT UNSIGNED NOT NULL, CHANGE rebate_agvideo_count rebate_agvideo_count INT UNSIGNED NOT NULL, CHANGE remit_count remit_count INT UNSIGNED NOT NULL, CHANGE offer_remit_count offer_remit_count INT UNSIGNED NOT NULL, CHANGE offer_company_remit_count offer_company_remit_count INT UNSIGNED NOT NULL");
    }
}
