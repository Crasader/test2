<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Util\Inflector;

/**
 * StatCashRebateRepository
 */
class StatCashRebateRepository extends AbstractStatCashRepository
{
    /**
     * 加總會員返點統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $limit     筆數限制
     * @param array $searchSet GroupHaving查詢條件
     * @param array $orderBy   排序條件
     * @return array
     */
    public function sumStatOfRebateByUser($criteria, $limit, $searchSet, $orderBy)
    {
        $qb = $this->createStatQueryBuilder($criteria, $limit, $searchSet, $orderBy);

        $qb->select('s.userId as user_id');
        $qb->addSelect('sum(s.rebateAmount) as rebate_amount');
        $qb->addSelect('sum(s.rebateCount) as rebate_count');
        $qb->addSelect('sum(s.rebateBallAmount) as rebate_ball_amount');
        $qb->addSelect('sum(s.rebateBallCount) as rebate_ball_count');
        $qb->addSelect('sum(s.rebateKenoAmount) as rebate_keno_amount');
        $qb->addSelect('sum(s.rebateKenoCount) as rebate_keno_count');
        $qb->addSelect('sum(s.rebateVideoAmount) as rebate_video_amount');
        $qb->addSelect('sum(s.rebateVideoCount) as rebate_video_count');
        $qb->addSelect('sum(s.rebateSportAmount) as rebate_sport_amount');
        $qb->addSelect('sum(s.rebateSportCount) as rebate_sport_count');
        $qb->addSelect('sum(s.rebateProbAmount) as rebate_prob_amount');
        $qb->addSelect('sum(s.rebateProbCount) as rebate_prob_count');
        $qb->addSelect('sum(s.rebateLotteryAmount) as rebate_lottery_amount');
        $qb->addSelect('sum(s.rebateLotteryCount) as rebate_lottery_count');
        $qb->addSelect('sum(s.rebateBBplayAmount) as rebate_bbplay_amount');
        $qb->addSelect('sum(s.rebateBBplayCount) as rebate_bbplay_count');
        $qb->addSelect('sum(s.rebateOfferAmount) as rebate_offer_amount');
        $qb->addSelect('sum(s.rebateOfferCount) as rebate_offer_count');
        $qb->addSelect('sum(s.rebateBBVideoAmount) as rebate_bbvideo_amount');
        $qb->addSelect('sum(s.rebateBBVideoCount) as rebate_bbvideo_count');
        $qb->addSelect('sum(s.rebateTTVideoAmount) as rebate_ttvideo_amount');
        $qb->addSelect('sum(s.rebateTTVideoCount) as rebate_ttvideo_count');
        $qb->addSelect('sum(s.rebateArmVideoAmount) as rebate_armvideo_amount');
        $qb->addSelect('sum(s.rebateArmVideoCount) as rebate_armvideo_count');
        $qb->addSelect('sum(s.rebateXpjVideoAmount) as rebate_xpjvideo_amount');
        $qb->addSelect('sum(s.rebateXpjVideoCount) as rebate_xpjvideo_count');
        $qb->addSelect('sum(s.rebateYfVideoAmount) as rebate_yfvideo_amount');
        $qb->addSelect('sum(s.rebateYfVideoCount) as rebate_yfvideo_count');
        $qb->addSelect('sum(s.rebate3dAmount) as rebate_3d_amount');
        $qb->addSelect('sum(s.rebate3dCount) as rebate_3d_count');
        $qb->addSelect('sum(s.rebateBattleAmount) as rebate_battle_amount');
        $qb->addSelect('sum(s.rebateBattleCount) as rebate_battle_count');
        $qb->addSelect('sum(s.rebateVirtualAmount) as rebate_virtual_amount');
        $qb->addSelect('sum(s.rebateVirtualCount) as rebate_virtual_count');
        $qb->addSelect('sum(s.rebateVipVideoAmount) as rebate_vipvideo_amount');
        $qb->addSelect('sum(s.rebateVipVideoCount) as rebate_vipvideo_count');
        $qb->addSelect('sum(s.rebateAgVideoAmount) as rebate_agvideo_amount');
        $qb->addSelect('sum(s.rebateAgVideoCount) as rebate_agvideo_count');
        $qb->addSelect('sum(s.rebatePTAmount) as rebate_pt_amount');
        $qb->addSelect('sum(s.rebatePTCount) as rebate_pt_count');
        $qb->addSelect('sum(s.rebateLTAmount) as rebate_lt_amount');
        $qb->addSelect('sum(s.rebateLTCount) as rebate_lt_count');
        $qb->addSelect('sum(s.rebateMIAmount) as rebate_mi_amount');
        $qb->addSelect('sum(s.rebateMICount) as rebate_mi_count');
        $qb->addSelect('sum(s.rebateABAmount) as rebate_ab_amount');
        $qb->addSelect('sum(s.rebateABCount) as rebate_ab_count');
        $qb->addSelect('sum(s.rebateMGAmount) as rebate_mg_amount');
        $qb->addSelect('sum(s.rebateMGCount) as rebate_mg_count');
        $qb->addSelect('sum(s.rebateOGAmount) as rebate_og_amount');
        $qb->addSelect('sum(s.rebateOGCount) as rebate_og_count');
        $qb->addSelect('sum(s.rebateSBAmount) as rebate_sb_amount');
        $qb->addSelect('sum(s.rebateSBCount) as rebate_sb_count');
        $qb->addSelect('sum(s.rebateGDAmount) as rebate_gd_amount');
        $qb->addSelect('sum(s.rebateGDCount) as rebate_gd_count');
        $qb->addSelect('sum(s.rebateSAAmount) as rebate_sa_amount');
        $qb->addSelect('sum(s.rebateSACount) as rebate_sa_count');
        $qb->addSelect('sum(s.rebateGnsAmount) as rebate_gns_amount');
        $qb->addSelect('sum(s.rebateGnsCount) as rebate_gns_count');
        $qb->addSelect('sum(s.rebateMGJackpotAmount) as rebate_mg_jackpot_amount');
        $qb->addSelect('sum(s.rebateMGJackpotCount) as rebate_mg_jackpot_count');
        $qb->addSelect('sum(s.rebateMGSlotsAmount) as rebate_mg_slots_amount');
        $qb->addSelect('sum(s.rebateMGSlotsCount) as rebate_mg_slots_count');
        $qb->addSelect('sum(s.rebateMGFeatureAmount) as rebate_mg_feature_amount');
        $qb->addSelect('sum(s.rebateMGFeatureCount) as rebate_mg_feature_count');
        $qb->addSelect('sum(s.rebateMGTableAmount) as rebate_mg_table_amount');
        $qb->addSelect('sum(s.rebateMGTableCount) as rebate_mg_table_count');
        $qb->addSelect('sum(s.rebateMGMobileAmount) as rebate_mg_mobile_amount');
        $qb->addSelect('sum(s.rebateMGMobileCount) as rebate_mg_mobile_count');
        $qb->addSelect('sum(s.rebateISBAmount) as rebate_isb_amount');
        $qb->addSelect('sum(s.rebateISBCount) as rebate_isb_count');
        $qb->addSelect('sum(s.rebateBBFGAmount) as rebate_bbfg_amount');
        $qb->addSelect('sum(s.rebateBBFGCount) as rebate_bbfg_count');
        $qb->addSelect('sum(s.rebateBCAmount) as rebate_bc_amount');
        $qb->addSelect('sum(s.rebateBCCount) as rebate_bc_count');
        $qb->addSelect('sum(s.rebateBBSlotsAmount) as rebate_bb_slots_amount');
        $qb->addSelect('sum(s.rebateBBSlotsCount) as rebate_bb_slots_count');
        $qb->addSelect('sum(s.rebateBBTableAmount) as rebate_bb_table_amount');
        $qb->addSelect('sum(s.rebateBBTableCount) as rebate_bb_table_count');
        $qb->addSelect('sum(s.rebateBBArcadeAmount) as rebate_bb_arcade_amount');
        $qb->addSelect('sum(s.rebateBBArcadeCount) as rebate_bb_arcade_count');
        $qb->addSelect('sum(s.rebateBBScratchAmount) as rebate_bb_scratch_amount');
        $qb->addSelect('sum(s.rebateBBScratchCount) as rebate_bb_scratch_count');
        $qb->addSelect('sum(s.rebateBBFeatureAmount) as rebate_bb_feature_amount');
        $qb->addSelect('sum(s.rebateBBFeatureCount) as rebate_bb_feature_count');
        $qb->addSelect('sum(s.rebateBBTreasureAmount) as rebate_bb_treasure_amount');
        $qb->addSelect('sum(s.rebateBBTreasureCount) as rebate_bb_treasure_count');
        $qb->addSelect('sum(s.rebateISBSlotsAmount) as rebate_isb_slots_amount');
        $qb->addSelect('sum(s.rebateISBSlotsCount) as rebate_isb_slots_count');
        $qb->addSelect('sum(s.rebateISBTableAmount) as rebate_isb_table_amount');
        $qb->addSelect('sum(s.rebateISBTableCount) as rebate_isb_table_count');
        $qb->addSelect('sum(s.rebateISBJackpotAmount) as rebate_isb_jackpot_amount');
        $qb->addSelect('sum(s.rebateISBJackpotCount) as rebate_isb_jackpot_count');
        $qb->addSelect('sum(s.rebateISBPokerAmount) as rebate_isb_poker_amount');
        $qb->addSelect('sum(s.rebateISBPokerCount) as rebate_isb_poker_count');
        $qb->addSelect('sum(s.rebate888FishingAmount) as rebate_888_fishing_amount');
        $qb->addSelect('sum(s.rebate888FishingCount) as rebate_888_fishing_count');
        $qb->addSelect('sum(s.rebatePTSlotsAmount) as rebate_pt_slots_amount');
        $qb->addSelect('sum(s.rebatePTSlotsCount) as rebate_pt_slots_count');
        $qb->addSelect('sum(s.rebatePTTableAmount) as rebate_pt_table_amount');
        $qb->addSelect('sum(s.rebatePTTableCount) as rebate_pt_table_count');
        $qb->addSelect('sum(s.rebatePTJackpotAmount) as rebate_pt_jackpot_amount');
        $qb->addSelect('sum(s.rebatePTJackpotCount) as rebate_pt_jackpot_count');
        $qb->addSelect('sum(s.rebatePTArcadeAmount) as rebate_pt_arcade_amount');
        $qb->addSelect('sum(s.rebatePTArcadeCount) as rebate_pt_arcade_count');
        $qb->addSelect('sum(s.rebatePTScratchAmount) as rebate_pt_scratch_amount');
        $qb->addSelect('sum(s.rebatePTScratchCount) as rebate_pt_scratch_count');
        $qb->addSelect('sum(s.rebatePTPokerAmount) as rebate_pt_poker_amount');
        $qb->addSelect('sum(s.rebatePTPokerCount) as rebate_pt_poker_count');
        $qb->addSelect('sum(s.rebatePTUnclassifiedAmount) as rebate_pt_unclassified_amount');
        $qb->addSelect('sum(s.rebatePTUnclassifiedCount) as rebate_pt_unclassified_count');
        $qb->addSelect('sum(s.rebateGOGAmount) as rebate_gog_amount');
        $qb->addSelect('sum(s.rebateGOGCount) as rebate_gog_count');
        $qb->addSelect('sum(s.rebateSk1Amount) as rebate_sk_1_amount');
        $qb->addSelect('sum(s.rebateSk1Count) as rebate_sk_1_count');
        $qb->addSelect('sum(s.rebateSk2Amount) as rebate_sk_2_amount');
        $qb->addSelect('sum(s.rebateSk2Count) as rebate_sk_2_count');
        $qb->addSelect('sum(s.rebateSk3Amount) as rebate_sk_3_amount');
        $qb->addSelect('sum(s.rebateSk3Count) as rebate_sk_3_count');
        $qb->addSelect('sum(s.rebateSk4Amount) as rebate_sk_4_amount');
        $qb->addSelect('sum(s.rebateSk4Count) as rebate_sk_4_count');
        $qb->addSelect('sum(s.rebateSk5Amount) as rebate_sk_5_amount');
        $qb->addSelect('sum(s.rebateSk5Count) as rebate_sk_5_count');
        $qb->addSelect('sum(s.rebateSk6Amount) as rebate_sk_6_amount');
        $qb->addSelect('sum(s.rebateSk6Count) as rebate_sk_6_count');
        $qb->addSelect('sum(s.rebateHBSlotsAmount) as rebate_hb_slots_amount');
        $qb->addSelect('sum(s.rebateHBSlotsCount) as rebate_hb_slots_count');
        $qb->addSelect('sum(s.rebateHBTableAmount) as rebate_hb_table_amount');
        $qb->addSelect('sum(s.rebateHBTableCount) as rebate_hb_table_count');
        $qb->addSelect('sum(s.rebateHBPokerAmount) as rebate_hb_poker_amount');
        $qb->addSelect('sum(s.rebateHBPokerCount) as rebate_hb_poker_count');
        $qb->addSelect('sum(s.rebateBGLiveAmount) as rebate_bg_live_amount');
        $qb->addSelect('sum(s.rebateBGLiveCount) as rebate_bg_live_count');
        $qb->addSelect('sum(s.rebateFishingMasterAmount) as rebate_fishing_master_amount');
        $qb->addSelect('sum(s.rebateFishingMasterCount) as rebate_fishing_master_count');
        $qb->addSelect('sum(s.rebatePPSlotsAmount) as rebate_pp_slots_amount');
        $qb->addSelect('sum(s.rebatePPSlotsCount) as rebate_pp_slots_count');
        $qb->addSelect('sum(s.rebatePPTableAmount) as rebate_pp_table_amount');
        $qb->addSelect('sum(s.rebatePPTableCount) as rebate_pp_table_count');
        $qb->addSelect('sum(s.rebatePPJackpotAmount) as rebate_pp_jackpot_amount');
        $qb->addSelect('sum(s.rebatePPJackpotCount) as rebate_pp_jackpot_count');
        $qb->addSelect('sum(s.rebatePPFeatureAmount) as rebate_pp_feature_amount');
        $qb->addSelect('sum(s.rebatePPFeatureCount) as rebate_pp_feature_count');
        $qb->addSelect('sum(s.rebatePTFishingAmount) as rebate_pt_fishing_amount');
        $qb->addSelect('sum(s.rebatePTFishingCount) as rebate_pt_fishing_count');
        $qb->addSelect('sum(s.rebateGNSSlotsAmount) as rebate_gns_slots_amount');
        $qb->addSelect('sum(s.rebateGNSSlotsCount) as rebate_gns_slots_count');
        $qb->addSelect('sum(s.rebateGNSFishingAmount) as rebate_gns_fishing_amount');
        $qb->addSelect('sum(s.rebateGNSFishingCount) as rebate_gns_fishing_count');
        $qb->addSelect('sum(s.rebateJDBSlotsAmount) as rebate_jdb_slots_amount');
        $qb->addSelect('sum(s.rebateJDBSlotsCount) as rebate_jdb_slots_count');
        $qb->addSelect('sum(s.rebateJDBArcadeAmount) as rebate_jdb_arcade_amount');
        $qb->addSelect('sum(s.rebateJDBArcadeCount) as rebate_jdb_arcade_count');
        $qb->addSelect('sum(s.rebateJDBFishingAmount) as rebate_jdb_fishing_amount');
        $qb->addSelect('sum(s.rebateJDBFishingCount) as rebate_jdb_fishing_count');
        $qb->addSelect('sum(s.rebateAgslotSlotsAmount) as rebate_agslot_slots_amount');
        $qb->addSelect('sum(s.rebateAgslotSlotsCount) as rebate_agslot_slots_count');
        $qb->addSelect('sum(s.rebateAgslotTableAmount) as rebate_agslot_table_amount');
        $qb->addSelect('sum(s.rebateAgslotTableCount) as rebate_agslot_table_count');
        $qb->addSelect('sum(s.rebateAgslotJackpotAmount) as rebate_agslot_jackpot_amount');
        $qb->addSelect('sum(s.rebateAgslotJackpotCount) as rebate_agslot_jackpot_count');
        $qb->addSelect('sum(s.rebateAgslotFishingAmount) as rebate_agslot_fishing_amount');
        $qb->addSelect('sum(s.rebateAgslotFishingCount) as rebate_agslot_fishing_count');
        $qb->addSelect('sum(s.rebateAgslotPokerAmount) as rebate_agslot_poker_amount');
        $qb->addSelect('sum(s.rebateAgslotPokerCount) as rebate_agslot_poker_count');
        $qb->addSelect('sum(s.rebateMWSlotsAmount) as rebate_mw_slots_amount');
        $qb->addSelect('sum(s.rebateMWSlotsCount) as rebate_mw_slots_count');
        $qb->addSelect('sum(s.rebateMWTableAmount) as rebate_mw_table_amount');
        $qb->addSelect('sum(s.rebateMWTableCount) as rebate_mw_table_count');
        $qb->addSelect('sum(s.rebateMWArcadeAmount) as rebate_mw_arcade_amount');
        $qb->addSelect('sum(s.rebateMWArcadeCount) as rebate_mw_arcade_count');
        $qb->addSelect('sum(s.rebateMWFishingAmount) as rebate_mw_fishing_amount');
        $qb->addSelect('sum(s.rebateMWFishingCount) as rebate_mw_fishing_count');
        $qb->addSelect('sum(s.rebateINSportAmount) as rebate_in_sport_amount');
        $qb->addSelect('sum(s.rebateINSportCount) as rebate_in_sport_count');
        $qb->addSelect('sum(s.rebateRTSlotsAmount) as rebate_rt_slots_amount');
        $qb->addSelect('sum(s.rebateRTSlotsCount) as rebate_rt_slots_count');
        $qb->addSelect('sum(s.rebateRTTableAmount) as rebate_rt_table_amount');
        $qb->addSelect('sum(s.rebateRTTableCount) as rebate_rt_table_count');
        $qb->addSelect('sum(s.rebateSGSlotsAmount) as rebate_sg_slots_amount');
        $qb->addSelect('sum(s.rebateSGSlotsCount) as rebate_sg_slots_count');
        $qb->addSelect('sum(s.rebateSGTableAmount) as rebate_sg_table_amount');
        $qb->addSelect('sum(s.rebateSGTableCount) as rebate_sg_table_count');
        $qb->addSelect('sum(s.rebateSGJackpotAmount) as rebate_sg_jackpot_amount');
        $qb->addSelect('sum(s.rebateSGJackpotCount) as rebate_sg_jackpot_count');
        $qb->addSelect('sum(s.rebateSGArcadeAmount) as rebate_sg_arcade_amount');
        $qb->addSelect('sum(s.rebateSGArcadeCount) as rebate_sg_arcade_count');
        $qb->addSelect('sum(s.rebateVRVrAmount) as rebate_vr_vr_amount');
        $qb->addSelect('sum(s.rebateVRVrCount) as rebate_vr_vr_count');
        $qb->addSelect('sum(s.rebateVRLottoAmount) as rebate_vr_lotto_amount');
        $qb->addSelect('sum(s.rebateVRLottoCount) as rebate_vr_lotto_count');
        $qb->addSelect('sum(s.rebateVRMarksixAmount) as rebate_vr_marksix_amount');
        $qb->addSelect('sum(s.rebateVRMarksixCount) as rebate_vr_marksix_count');
        $qb->addSelect('sum(s.rebatePT2SlotsAmount) as rebate_pt2_slots_amount');
        $qb->addSelect('sum(s.rebatePT2SlotsCount) as rebate_pt2_slots_count');
        $qb->addSelect('sum(s.rebatePT2JackpotAmount) as rebate_pt2_jackpot_amount');
        $qb->addSelect('sum(s.rebatePT2JackpotCount) as rebate_pt2_jackpot_count');
        $qb->addSelect('sum(s.rebatePT2FishingAmount) as rebate_pt2_fishing_amount');
        $qb->addSelect('sum(s.rebatePT2FishingCount) as rebate_pt2_fishing_count');
        $qb->addSelect('sum(s.rebatePT2TableAmount) as rebate_pt2_table_amount');
        $qb->addSelect('sum(s.rebatePT2TableCount) as rebate_pt2_table_count');
        $qb->addSelect('sum(s.rebatePT2FeatureAmount) as rebate_pt2_feature_amount');
        $qb->addSelect('sum(s.rebatePT2FeatureCount) as rebate_pt2_feature_count');
        $qb->addSelect('sum(s.rebateBngSlotsAmount) as rebate_bng_slots_amount');
        $qb->addSelect('sum(s.rebateBngSlotsCount) as rebate_bng_slots_count');
        $qb->addSelect('sum(s.rebateEVOAmount) as rebate_evo_amount');
        $qb->addSelect('sum(s.rebateEVOCount) as rebate_evo_count');
        $qb->addSelect('sum(s.rebateGNSJackpotAmount) as rebate_gns_jackpot_amount');
        $qb->addSelect('sum(s.rebateGNSJackpotCount) as rebate_gns_jackpot_count');
        $qb->addSelect('sum(s.rebateGNSFeatureAmount) as rebate_gns_feature_amount');
        $qb->addSelect('sum(s.rebateGNSFeatureCount) as rebate_gns_feature_count');
        $qb->addSelect('sum(s.rebateKYAmount) as rebate_ky_amount');
        $qb->addSelect('sum(s.rebateKYCount) as rebate_ky_count');
        $qb->addSelect('sum(s.rebateGNSTableGamesAmount) as rebate_gns_table_amount');
        $qb->addSelect('sum(s.rebateGNSTableGamesCount) as rebate_gns_table_count');
        $qb->groupBy('s.userId');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳有返點統計記錄的會員數
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return integer
     */
    public function countNumOfRebate($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, [], $searchSet);

        $qb->select('COUNT(s.userId)');
        $qb->groupBy('s.userId');

        return count($qb->getQuery()->getArrayResult());
    }

    /**
     * 小計會員返點統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return array
     */
    public function sumStatOfRebate($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, null, $searchSet);

        $qb->select('sum(s.rebateAmount) as rebate_amount');
        $qb->addSelect('sum(s.rebateCount) as rebate_count');
        $qb->addSelect('sum(s.rebateBallAmount) as rebate_ball_amount');
        $qb->addSelect('sum(s.rebateBallCount) as rebate_ball_count');
        $qb->addSelect('sum(s.rebateKenoAmount) as rebate_keno_amount');
        $qb->addSelect('sum(s.rebateKenoCount) as rebate_keno_count');
        $qb->addSelect('sum(s.rebateVideoAmount) as rebate_video_amount');
        $qb->addSelect('sum(s.rebateVideoCount) as rebate_video_count');
        $qb->addSelect('sum(s.rebateSportAmount) as rebate_sport_amount');
        $qb->addSelect('sum(s.rebateSportCount) as rebate_sport_count');
        $qb->addSelect('sum(s.rebateProbAmount) as rebate_prob_amount');
        $qb->addSelect('sum(s.rebateProbCount) as rebate_prob_count');
        $qb->addSelect('sum(s.rebateLotteryAmount) as rebate_lottery_amount');
        $qb->addSelect('sum(s.rebateLotteryCount) as rebate_lottery_count');
        $qb->addSelect('sum(s.rebateBBplayAmount) as rebate_bbplay_amount');
        $qb->addSelect('sum(s.rebateBBplayCount) as rebate_bbplay_count');
        $qb->addSelect('sum(s.rebateOfferAmount) as rebate_offer_amount');
        $qb->addSelect('sum(s.rebateOfferCount) as rebate_offer_count');
        $qb->addSelect('sum(s.rebateBBVideoAmount) as rebate_bbvideo_amount');
        $qb->addSelect('sum(s.rebateBBVideoCount) as rebate_bbvideo_count');
        $qb->addSelect('sum(s.rebateTTVideoAmount) as rebate_ttvideo_amount');
        $qb->addSelect('sum(s.rebateTTVideoCount) as rebate_ttvideo_count');
        $qb->addSelect('sum(s.rebateArmVideoAmount) as rebate_armvideo_amount');
        $qb->addSelect('sum(s.rebateArmVideoCount) as rebate_armvideo_count');
        $qb->addSelect('sum(s.rebateXpjVideoAmount) as rebate_xpjvideo_amount');
        $qb->addSelect('sum(s.rebateXpjVideoCount) as rebate_xpjvideo_count');
        $qb->addSelect('sum(s.rebateYfVideoAmount) as rebate_yfvideo_amount');
        $qb->addSelect('sum(s.rebateYfVideoCount) as rebate_yfvideo_count');
        $qb->addSelect('sum(s.rebate3dAmount) as rebate_3d_amount');
        $qb->addSelect('sum(s.rebate3dCount) as rebate_3d_count');
        $qb->addSelect('sum(s.rebateBattleAmount) as rebate_battle_amount');
        $qb->addSelect('sum(s.rebateBattleCount) as rebate_battle_count');
        $qb->addSelect('sum(s.rebateVirtualAmount) as rebate_virtual_amount');
        $qb->addSelect('sum(s.rebateVirtualCount) as rebate_virtual_count');
        $qb->addSelect('sum(s.rebateVipVideoAmount) as rebate_vipvideo_amount');
        $qb->addSelect('sum(s.rebateVipVideoCount) as rebate_vipvideo_count');
        $qb->addSelect('sum(s.rebateAgVideoAmount) as rebate_agvideo_amount');
        $qb->addSelect('sum(s.rebateAgVideoCount) as rebate_agvideo_count');
        $qb->addSelect('sum(s.rebatePTAmount) as rebate_pt_amount');
        $qb->addSelect('sum(s.rebatePTCount) as rebate_pt_count');
        $qb->addSelect('sum(s.rebateLTAmount) as rebate_lt_amount');
        $qb->addSelect('sum(s.rebateLTCount) as rebate_lt_count');
        $qb->addSelect('sum(s.rebateMIAmount) as rebate_mi_amount');
        $qb->addSelect('sum(s.rebateMICount) as rebate_mi_count');
        $qb->addSelect('sum(s.rebateABAmount) as rebate_ab_amount');
        $qb->addSelect('sum(s.rebateABCount) as rebate_ab_count');
        $qb->addSelect('sum(s.rebateMGAmount) as rebate_mg_amount');
        $qb->addSelect('sum(s.rebateMGCount) as rebate_mg_count');
        $qb->addSelect('sum(s.rebateOGAmount) as rebate_og_amount');
        $qb->addSelect('sum(s.rebateOGCount) as rebate_og_count');
        $qb->addSelect('sum(s.rebateSBAmount) as rebate_sb_amount');
        $qb->addSelect('sum(s.rebateSBCount) as rebate_sb_count');
        $qb->addSelect('sum(s.rebateGDAmount) as rebate_gd_amount');
        $qb->addSelect('sum(s.rebateGDCount) as rebate_gd_count');
        $qb->addSelect('sum(s.rebateSAAmount) as rebate_sa_amount');
        $qb->addSelect('sum(s.rebateSACount) as rebate_sa_count');
        $qb->addSelect('sum(s.rebateGnsAmount) as rebate_gns_amount');
        $qb->addSelect('sum(s.rebateGnsCount) as rebate_gns_count');
        $qb->addSelect('sum(s.rebateMGJackpotAmount) as rebate_mg_jackpot_amount');
        $qb->addSelect('sum(s.rebateMGJackpotCount) as rebate_mg_jackpot_count');
        $qb->addSelect('sum(s.rebateMGSlotsAmount) as rebate_mg_slots_amount');
        $qb->addSelect('sum(s.rebateMGSlotsCount) as rebate_mg_slots_count');
        $qb->addSelect('sum(s.rebateMGFeatureAmount) as rebate_mg_feature_amount');
        $qb->addSelect('sum(s.rebateMGFeatureCount) as rebate_mg_feature_count');
        $qb->addSelect('sum(s.rebateMGTableAmount) as rebate_mg_table_amount');
        $qb->addSelect('sum(s.rebateMGTableCount) as rebate_mg_table_count');
        $qb->addSelect('sum(s.rebateMGMobileAmount) as rebate_mg_mobile_amount');
        $qb->addSelect('sum(s.rebateMGMobileCount) as rebate_mg_mobile_count');
        $qb->addSelect('sum(s.rebateISBAmount) as rebate_isb_amount');
        $qb->addSelect('sum(s.rebateISBCount) as rebate_isb_count');
        $qb->addSelect('sum(s.rebateBBFGAmount) as rebate_bbfg_amount');
        $qb->addSelect('sum(s.rebateBBFGCount) as rebate_bbfg_count');
        $qb->addSelect('sum(s.rebateBCAmount) as rebate_bc_amount');
        $qb->addSelect('sum(s.rebateBCCount) as rebate_bc_count');
        $qb->addSelect('sum(s.rebateBBSlotsAmount) as rebate_bb_slots_amount');
        $qb->addSelect('sum(s.rebateBBSlotsCount) as rebate_bb_slots_count');
        $qb->addSelect('sum(s.rebateBBTableAmount) as rebate_bb_table_amount');
        $qb->addSelect('sum(s.rebateBBTableCount) as rebate_bb_table_count');
        $qb->addSelect('sum(s.rebateBBArcadeAmount) as rebate_bb_arcade_amount');
        $qb->addSelect('sum(s.rebateBBArcadeCount) as rebate_bb_arcade_count');
        $qb->addSelect('sum(s.rebateBBScratchAmount) as rebate_bb_scratch_amount');
        $qb->addSelect('sum(s.rebateBBScratchCount) as rebate_bb_scratch_count');
        $qb->addSelect('sum(s.rebateBBFeatureAmount) as rebate_bb_feature_amount');
        $qb->addSelect('sum(s.rebateBBFeatureCount) as rebate_bb_feature_count');
        $qb->addSelect('sum(s.rebateBBTreasureAmount) as rebate_bb_treasure_amount');
        $qb->addSelect('sum(s.rebateBBTreasureCount) as rebate_bb_treasure_count');
        $qb->addSelect('sum(s.rebateISBSlotsAmount) as rebate_isb_slots_amount');
        $qb->addSelect('sum(s.rebateISBSlotsCount) as rebate_isb_slots_count');
        $qb->addSelect('sum(s.rebateISBTableAmount) as rebate_isb_table_amount');
        $qb->addSelect('sum(s.rebateISBTableCount) as rebate_isb_table_count');
        $qb->addSelect('sum(s.rebateISBJackpotAmount) as rebate_isb_jackpot_amount');
        $qb->addSelect('sum(s.rebateISBJackpotCount) as rebate_isb_jackpot_count');
        $qb->addSelect('sum(s.rebateISBPokerAmount) as rebate_isb_poker_amount');
        $qb->addSelect('sum(s.rebateISBPokerCount) as rebate_isb_poker_count');
        $qb->addSelect('sum(s.rebate888FishingAmount) as rebate_888_fishing_amount');
        $qb->addSelect('sum(s.rebate888FishingCount) as rebate_888_fishing_count');
        $qb->addSelect('sum(s.rebatePTSlotsAmount) as rebate_pt_slots_amount');
        $qb->addSelect('sum(s.rebatePTSlotsCount) as rebate_pt_slots_count');
        $qb->addSelect('sum(s.rebatePTTableAmount) as rebate_pt_table_amount');
        $qb->addSelect('sum(s.rebatePTTableCount) as rebate_pt_table_count');
        $qb->addSelect('sum(s.rebatePTJackpotAmount) as rebate_pt_jackpot_amount');
        $qb->addSelect('sum(s.rebatePTJackpotCount) as rebate_pt_jackpot_count');
        $qb->addSelect('sum(s.rebatePTArcadeAmount) as rebate_pt_arcade_amount');
        $qb->addSelect('sum(s.rebatePTArcadeCount) as rebate_pt_arcade_count');
        $qb->addSelect('sum(s.rebatePTScratchAmount) as rebate_pt_scratch_amount');
        $qb->addSelect('sum(s.rebatePTScratchCount) as rebate_pt_scratch_count');
        $qb->addSelect('sum(s.rebatePTPokerAmount) as rebate_pt_poker_amount');
        $qb->addSelect('sum(s.rebatePTPokerCount) as rebate_pt_poker_count');
        $qb->addSelect('sum(s.rebatePTUnclassifiedAmount) as rebate_pt_unclassified_amount');
        $qb->addSelect('sum(s.rebatePTUnclassifiedCount) as rebate_pt_unclassified_count');
        $qb->addSelect('sum(s.rebateGOGAmount) as rebate_gog_amount');
        $qb->addSelect('sum(s.rebateGOGCount) as rebate_gog_count');
        $qb->addSelect('sum(s.rebateSk1Amount) as rebate_sk_1_amount');
        $qb->addSelect('sum(s.rebateSk1Count) as rebate_sk_1_count');
        $qb->addSelect('sum(s.rebateSk2Amount) as rebate_sk_2_amount');
        $qb->addSelect('sum(s.rebateSk2Count) as rebate_sk_2_count');
        $qb->addSelect('sum(s.rebateSk3Amount) as rebate_sk_3_amount');
        $qb->addSelect('sum(s.rebateSk3Count) as rebate_sk_3_count');
        $qb->addSelect('sum(s.rebateSk4Amount) as rebate_sk_4_amount');
        $qb->addSelect('sum(s.rebateSk4Count) as rebate_sk_4_count');
        $qb->addSelect('sum(s.rebateSk5Amount) as rebate_sk_5_amount');
        $qb->addSelect('sum(s.rebateSk5Count) as rebate_sk_5_count');
        $qb->addSelect('sum(s.rebateSk6Amount) as rebate_sk_6_amount');
        $qb->addSelect('sum(s.rebateSk6Count) as rebate_sk_6_count');
        $qb->addSelect('sum(s.rebateHBSlotsAmount) as rebate_hb_slots_amount');
        $qb->addSelect('sum(s.rebateHBSlotsCount) as rebate_hb_slots_count');
        $qb->addSelect('sum(s.rebateHBTableAmount) as rebate_hb_table_amount');
        $qb->addSelect('sum(s.rebateHBTableCount) as rebate_hb_table_count');
        $qb->addSelect('sum(s.rebateHBPokerAmount) as rebate_hb_poker_amount');
        $qb->addSelect('sum(s.rebateHBPokerCount) as rebate_hb_poker_count');
        $qb->addSelect('sum(s.rebateBGLiveAmount) as rebate_bg_live_amount');
        $qb->addSelect('sum(s.rebateBGLiveCount) as rebate_bg_live_count');
        $qb->addSelect('sum(s.rebateFishingMasterAmount) as rebate_fishing_master_amount');
        $qb->addSelect('sum(s.rebateFishingMasterCount) as rebate_fishing_master_count');
        $qb->addSelect('sum(s.rebatePPSlotsAmount) as rebate_pp_slots_amount');
        $qb->addSelect('sum(s.rebatePPSlotsCount) as rebate_pp_slots_count');
        $qb->addSelect('sum(s.rebatePPTableAmount) as rebate_pp_table_amount');
        $qb->addSelect('sum(s.rebatePPTableCount) as rebate_pp_table_count');
        $qb->addSelect('sum(s.rebatePPJackpotAmount) as rebate_pp_jackpot_amount');
        $qb->addSelect('sum(s.rebatePPJackpotCount) as rebate_pp_jackpot_count');
        $qb->addSelect('sum(s.rebatePPFeatureAmount) as rebate_pp_feature_amount');
        $qb->addSelect('sum(s.rebatePPFeatureCount) as rebate_pp_feature_count');
        $qb->addSelect('sum(s.rebatePTFishingAmount) as rebate_pt_fishing_amount');
        $qb->addSelect('sum(s.rebatePTFishingCount) as rebate_pt_fishing_count');
        $qb->addSelect('sum(s.rebateGNSSlotsAmount) as rebate_gns_slots_amount');
        $qb->addSelect('sum(s.rebateGNSSlotsCount) as rebate_gns_slots_count');
        $qb->addSelect('sum(s.rebateGNSFishingAmount) as rebate_gns_fishing_amount');
        $qb->addSelect('sum(s.rebateGNSFishingCount) as rebate_gns_fishing_count');
        $qb->addSelect('sum(s.rebateJDBSlotsAmount) as rebate_jdb_slots_amount');
        $qb->addSelect('sum(s.rebateJDBSlotsCount) as rebate_jdb_slots_count');
        $qb->addSelect('sum(s.rebateJDBArcadeAmount) as rebate_jdb_arcade_amount');
        $qb->addSelect('sum(s.rebateJDBArcadeCount) as rebate_jdb_arcade_count');
        $qb->addSelect('sum(s.rebateJDBFishingAmount) as rebate_jdb_fishing_amount');
        $qb->addSelect('sum(s.rebateJDBFishingCount) as rebate_jdb_fishing_count');
        $qb->addSelect('sum(s.rebateAgslotSlotsAmount) as rebate_agslot_slots_amount');
        $qb->addSelect('sum(s.rebateAgslotSlotsCount) as rebate_agslot_slots_count');
        $qb->addSelect('sum(s.rebateAgslotTableAmount) as rebate_agslot_table_amount');
        $qb->addSelect('sum(s.rebateAgslotTableCount) as rebate_agslot_table_count');
        $qb->addSelect('sum(s.rebateAgslotJackpotAmount) as rebate_agslot_jackpot_amount');
        $qb->addSelect('sum(s.rebateAgslotJackpotCount) as rebate_agslot_jackpot_count');
        $qb->addSelect('sum(s.rebateAgslotFishingAmount) as rebate_agslot_fishing_amount');
        $qb->addSelect('sum(s.rebateAgslotFishingCount) as rebate_agslot_fishing_count');
        $qb->addSelect('sum(s.rebateAgslotPokerAmount) as rebate_agslot_poker_amount');
        $qb->addSelect('sum(s.rebateAgslotPokerCount) as rebate_agslot_poker_count');
        $qb->addSelect('sum(s.rebateMWSlotsAmount) as rebate_mw_slots_amount');
        $qb->addSelect('sum(s.rebateMWSlotsCount) as rebate_mw_slots_count');
        $qb->addSelect('sum(s.rebateMWTableAmount) as rebate_mw_table_amount');
        $qb->addSelect('sum(s.rebateMWTableCount) as rebate_mw_table_count');
        $qb->addSelect('sum(s.rebateMWArcadeAmount) as rebate_mw_arcade_amount');
        $qb->addSelect('sum(s.rebateMWArcadeCount) as rebate_mw_arcade_count');
        $qb->addSelect('sum(s.rebateMWFishingAmount) as rebate_mw_fishing_amount');
        $qb->addSelect('sum(s.rebateMWFishingCount) as rebate_mw_fishing_count');
        $qb->addSelect('sum(s.rebateINSportAmount) as rebate_in_sport_amount');
        $qb->addSelect('sum(s.rebateINSportCount) as rebate_in_sport_count');
        $qb->addSelect('sum(s.rebateRTSlotsAmount) as rebate_rt_slots_amount');
        $qb->addSelect('sum(s.rebateRTSlotsCount) as rebate_rt_slots_count');
        $qb->addSelect('sum(s.rebateRTTableAmount) as rebate_rt_table_amount');
        $qb->addSelect('sum(s.rebateRTTableCount) as rebate_rt_table_count');
        $qb->addSelect('sum(s.rebateSGSlotsAmount) as rebate_sg_slots_amount');
        $qb->addSelect('sum(s.rebateSGSlotsCount) as rebate_sg_slots_count');
        $qb->addSelect('sum(s.rebateSGTableAmount) as rebate_sg_table_amount');
        $qb->addSelect('sum(s.rebateSGTableCount) as rebate_sg_table_count');
        $qb->addSelect('sum(s.rebateSGJackpotAmount) as rebate_sg_jackpot_amount');
        $qb->addSelect('sum(s.rebateSGJackpotCount) as rebate_sg_jackpot_count');
        $qb->addSelect('sum(s.rebateSGArcadeAmount) as rebate_sg_arcade_amount');
        $qb->addSelect('sum(s.rebateSGArcadeCount) as rebate_sg_arcade_count');
        $qb->addSelect('sum(s.rebateVRVrAmount) as rebate_vr_vr_amount');
        $qb->addSelect('sum(s.rebateVRVrCount) as rebate_vr_vr_count');
        $qb->addSelect('sum(s.rebateVRLottoAmount) as rebate_vr_lotto_amount');
        $qb->addSelect('sum(s.rebateVRLottoCount) as rebate_vr_lotto_count');
        $qb->addSelect('sum(s.rebateVRMarksixAmount) as rebate_vr_marksix_amount');
        $qb->addSelect('sum(s.rebateVRMarksixCount) as rebate_vr_marksix_count');
        $qb->addSelect('sum(s.rebatePT2SlotsAmount) as rebate_pt2_slots_amount');
        $qb->addSelect('sum(s.rebatePT2SlotsCount) as rebate_pt2_slots_count');
        $qb->addSelect('sum(s.rebatePT2JackpotAmount) as rebate_pt2_jackpot_amount');
        $qb->addSelect('sum(s.rebatePT2JackpotCount) as rebate_pt2_jackpot_count');
        $qb->addSelect('sum(s.rebatePT2FishingAmount) as rebate_pt2_fishing_amount');
        $qb->addSelect('sum(s.rebatePT2FishingCount) as rebate_pt2_fishing_count');
        $qb->addSelect('sum(s.rebatePT2TableAmount) as rebate_pt2_table_amount');
        $qb->addSelect('sum(s.rebatePT2TableCount) as rebate_pt2_table_count');
        $qb->addSelect('sum(s.rebatePT2FeatureAmount) as rebate_pt2_feature_amount');
        $qb->addSelect('sum(s.rebatePT2FeatureCount) as rebate_pt2_feature_count');
        $qb->addSelect('sum(s.rebateBngSlotsAmount) as rebate_bng_slots_amount');
        $qb->addSelect('sum(s.rebateBngSlotsCount) as rebate_bng_slots_count');
        $qb->addSelect('sum(s.rebateEVOAmount) as rebate_evo_amount');
        $qb->addSelect('sum(s.rebateEVOCount) as rebate_evo_count');
        $qb->addSelect('sum(s.rebateGNSJackpotAmount) as rebate_gns_jackpot_amount');
        $qb->addSelect('sum(s.rebateGNSJackpotCount) as rebate_gns_jackpot_count');
        $qb->addSelect('sum(s.rebateGNSFeatureAmount) as rebate_gns_feature_amount');
        $qb->addSelect('sum(s.rebateGNSFeatureCount) as rebate_gns_feature_count');
        $qb->addSelect('sum(s.rebateKYAmount) as rebate_ky_amount');
        $qb->addSelect('sum(s.rebateKYCount) as rebate_ky_count');
        $qb->addSelect('sum(s.rebateGNSTableGamesAmount) as rebate_gns_table_amount');
        $qb->addSelect('sum(s.rebateGNSTableGamesCount) as rebate_gns_table_count');
        $qb->groupBy('s.userId');

        $arrayResults = $qb->getQuery()->getArrayResult();
        $ret = [];

        if ($arrayResults) {
            $ret = [
                'rebate_amount' => 0,
                'rebate_count' => 0,
                'rebate_ball_amount' => 0,
                'rebate_ball_count' => 0,
                'rebate_keno_amount' => 0,
                'rebate_keno_count' => 0,
                'rebate_video_amount' => 0,
                'rebate_video_count' => 0,
                'rebate_sport_amount' => 0,
                'rebate_sport_count' => 0,
                'rebate_prob_amount' => 0,
                'rebate_prob_count' => 0,
                'rebate_lottery_amount' => 0,
                'rebate_lottery_count' => 0,
                'rebate_bbplay_amount' => 0,
                'rebate_bbplay_count' => 0,
                'rebate_offer_amount' => 0,
                'rebate_offer_count' => 0,
                'rebate_bbvideo_amount' => 0,
                'rebate_bbvideo_count' => 0,
                'rebate_ttvideo_amount' => 0,
                'rebate_ttvideo_count' => 0,
                'rebate_armvideo_amount' => 0,
                'rebate_armvideo_count' => 0,
                'rebate_xpjvideo_amount' => 0,
                'rebate_xpjvideo_count' => 0,
                'rebate_yfvideo_amount' => 0,
                'rebate_yfvideo_count' => 0,
                'rebate_3d_amount' => 0,
                'rebate_3d_count' => 0,
                'rebate_battle_amount' => 0,
                'rebate_battle_count' => 0,
                'rebate_virtual_amount' => 0,
                'rebate_virtual_count' => 0,
                'rebate_vipvideo_amount' => 0,
                'rebate_vipvideo_count' => 0,
                'rebate_agvideo_amount' => 0,
                'rebate_agvideo_count' => 0,
                'rebate_pt_amount' => 0,
                'rebate_pt_count' => 0,
                'rebate_lt_amount' => 0,
                'rebate_lt_count' => 0,
                'rebate_mi_amount' => 0,
                'rebate_mi_count' => 0,
                'rebate_ab_amount' => 0,
                'rebate_ab_count' => 0,
                'rebate_mg_amount' => 0,
                'rebate_mg_count' => 0,
                'rebate_og_amount' => 0,
                'rebate_og_count' => 0,
                'rebate_sb_amount' => 0,
                'rebate_sb_count' => 0,
                'rebate_gd_amount' => 0,
                'rebate_gd_count' => 0,
                'rebate_sa_amount' => 0,
                'rebate_sa_count' => 0,
                'rebate_gns_amount' => 0,
                'rebate_gns_count' => 0,
                'rebate_mg_jackpot_amount' => 0,
                'rebate_mg_jackpot_count' => 0,
                'rebate_mg_slots_amount' => 0,
                'rebate_mg_slots_count' => 0,
                'rebate_mg_feature_amount' => 0,
                'rebate_mg_feature_count' => 0,
                'rebate_mg_table_amount' => 0,
                'rebate_mg_table_count' => 0,
                'rebate_mg_mobile_amount' => 0,
                'rebate_mg_mobile_count' => 0,
                'rebate_isb_amount' => 0,
                'rebate_isb_count' => 0,
                'rebate_bbfg_amount' => 0,
                'rebate_bbfg_count' => 0,
                'rebate_bc_amount' => 0,
                'rebate_bc_count' => 0,
                'rebate_bb_slots_amount' => 0,
                'rebate_bb_slots_count' => 0,
                'rebate_bb_table_amount' => 0,
                'rebate_bb_table_count' => 0,
                'rebate_bb_arcade_amount' => 0,
                'rebate_bb_arcade_count' => 0,
                'rebate_bb_scratch_amount' => 0,
                'rebate_bb_scratch_count' => 0,
                'rebate_bb_feature_amount' => 0,
                'rebate_bb_feature_count' => 0,
                'rebate_bb_treasure_amount' => 0,
                'rebate_bb_treasure_count' => 0,
                'rebate_isb_slots_amount' => 0,
                'rebate_isb_slots_count' => 0,
                'rebate_isb_table_amount' => 0,
                'rebate_isb_table_count' => 0,
                'rebate_isb_jackpot_amount' => 0,
                'rebate_isb_jackpot_count' => 0,
                'rebate_isb_poker_amount' => 0,
                'rebate_isb_poker_count' => 0,
                'rebate_888_fishing_amount' => 0,
                'rebate_888_fishing_count' => 0,
                'rebate_pt_slots_amount' => 0,
                'rebate_pt_slots_count' => 0,
                'rebate_pt_table_amount' => 0,
                'rebate_pt_table_count' => 0,
                'rebate_pt_jackpot_amount' => 0,
                'rebate_pt_jackpot_count' => 0,
                'rebate_pt_arcade_amount' => 0,
                'rebate_pt_arcade_count' => 0,
                'rebate_pt_scratch_amount' => 0,
                'rebate_pt_scratch_count' => 0,
                'rebate_pt_poker_amount' => 0,
                'rebate_pt_poker_count' => 0,
                'rebate_pt_unclassified_amount' => 0,
                'rebate_pt_unclassified_count' => 0,
                'rebate_gog_amount' => 0,
                'rebate_gog_count' => 0,
                'rebate_sk_1_amount' => 0,
                'rebate_sk_1_count' => 0,
                'rebate_sk_2_amount' => 0,
                'rebate_sk_2_count' => 0,
                'rebate_sk_3_amount' => 0,
                'rebate_sk_3_count' => 0,
                'rebate_sk_4_amount' => 0,
                'rebate_sk_4_count' => 0,
                'rebate_sk_5_amount' => 0,
                'rebate_sk_5_count' => 0,
                'rebate_sk_6_amount' => 0,
                'rebate_sk_6_count' => 0,
                'rebate_hb_slots_amount' => 0,
                'rebate_hb_slots_count' => 0,
                'rebate_hb_table_amount' => 0,
                'rebate_hb_table_count' => 0,
                'rebate_hb_poker_amount' => 0,
                'rebate_hb_poker_count' => 0,
                'rebate_bg_live_amount' => 0,
                'rebate_bg_live_count' => 0,
                'rebate_fishing_master_amount' => 0,
                'rebate_fishing_master_count' => 0,
                'rebate_pp_slots_amount' => 0,
                'rebate_pp_slots_count' => 0,
                'rebate_pp_table_amount' => 0,
                'rebate_pp_table_count' => 0,
                'rebate_pp_jackpot_amount' => 0,
                'rebate_pp_jackpot_count' => 0,
                'rebate_pp_feature_amount' => 0,
                'rebate_pp_feature_count' => 0,
                'rebate_pt_fishing_amount' => 0,
                'rebate_pt_fishing_count' => 0,
                'rebate_gns_slots_amount' => 0,
                'rebate_gns_slots_count' => 0,
                'rebate_gns_fishing_amount' => 0,
                'rebate_gns_fishing_count' => 0,
                'rebate_jdb_slots_amount' => 0,
                'rebate_jdb_slots_count' => 0,
                'rebate_jdb_arcade_amount' => 0,
                'rebate_jdb_arcade_count' => 0,
                'rebate_jdb_fishing_amount' => 0,
                'rebate_jdb_fishing_count' => 0,
                'rebate_agslot_slots_amount' => 0,
                'rebate_agslot_slots_count' => 0,
                'rebate_agslot_table_amount' => 0,
                'rebate_agslot_table_count' => 0,
                'rebate_agslot_jackpot_amount' => 0,
                'rebate_agslot_jackpot_count' => 0,
                'rebate_agslot_fishing_amount' => 0,
                'rebate_agslot_fishing_count' => 0,
                'rebate_agslot_poker_amount' => 0,
                'rebate_agslot_poker_count' => 0,
                'rebate_mw_slots_amount' => 0,
                'rebate_mw_slots_count' => 0,
                'rebate_mw_table_amount' => 0,
                'rebate_mw_table_count' => 0,
                'rebate_mw_arcade_amount' => 0,
                'rebate_mw_arcade_count' => 0,
                'rebate_mw_fishing_amount' => 0,
                'rebate_mw_fishing_count' => 0,
                'rebate_in_sport_amount' => 0,
                'rebate_in_sport_count' => 0,
                'rebate_rt_slots_amount' => 0,
                'rebate_rt_slots_count' => 0,
                'rebate_rt_table_amount' => 0,
                'rebate_rt_table_count' => 0,
                'rebate_sg_slots_amount' => 0,
                'rebate_sg_slots_count' => 0,
                'rebate_sg_table_amount' => 0,
                'rebate_sg_table_count' => 0,
                'rebate_sg_jackpot_amount' => 0,
                'rebate_sg_jackpot_count' => 0,
                'rebate_sg_arcade_amount' => 0,
                'rebate_sg_arcade_count' => 0,
                'rebate_vr_vr_amount' => 0,
                'rebate_vr_vr_count' => 0,
                'rebate_vr_lotto_amount' => 0,
                'rebate_vr_lotto_count' => 0,
                'rebate_vr_marksix_amount' => 0,
                'rebate_vr_marksix_count' => 0,
                'rebate_pt2_slots_amount' => 0,
                'rebate_pt2_slots_count' => 0,
                'rebate_pt2_jackpot_amount' => 0,
                'rebate_pt2_jackpot_count' => 0,
                'rebate_pt2_fishing_amount' => 0,
                'rebate_pt2_fishing_count' => 0,
                'rebate_pt2_table_amount' => 0,
                'rebate_pt2_table_count' => 0,
                'rebate_pt2_feature_amount' => 0,
                'rebate_pt2_feature_count' => 0,
                'rebate_bng_slots_amount' => 0,
                'rebate_bng_slots_count' => 0,
                'rebate_evo_amount' => 0,
                'rebate_evo_count' => 0,
                'rebate_gns_jackpot_amount' => 0,
                'rebate_gns_jackpot_count' => 0,
                'rebate_gns_feature_amount' => 0,
                'rebate_gns_feature_count' => 0,
                'rebate_ky_amount' => 0,
                'rebate_ky_count' => 0,
                'rebate_gns_table_amount' => 0,
                'rebate_gns_table_count' => 0
            ];

            foreach ($arrayResults as $arrayResult) {
                foreach ($arrayResult as $key => $value) {
                    $ret[$key] += $value;
                }
            }
        }

        return $ret;
    }

    /**
     * 加總代理返點統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $limit     筆數限制
     * @param array $searchSet GroupHaving查詢條件
     * @param array $orderBy   排序條件
     * @return array
     */
    public function sumStatOfRebateByParentId($criteria, $limit, $searchSet, $orderBy)
    {
        $qb = $this->createStatQueryBuilder($criteria, $limit, $searchSet, $orderBy);

        $qb->select('s.parentId as user_id');
        $qb->addSelect('count(DISTINCT s.userId) as total_user');
        $qb->addSelect('sum(s.rebateAmount) as rebate_amount');
        $qb->addSelect('sum(s.rebateCount) as rebate_count');
        $qb->addSelect('sum(s.rebateBallAmount) as rebate_ball_amount');
        $qb->addSelect('sum(s.rebateBallCount) as rebate_ball_count');
        $qb->addSelect('sum(s.rebateKenoAmount) as rebate_keno_amount');
        $qb->addSelect('sum(s.rebateKenoCount) as rebate_keno_count');
        $qb->addSelect('sum(s.rebateVideoAmount) as rebate_video_amount');
        $qb->addSelect('sum(s.rebateVideoCount) as rebate_video_count');
        $qb->addSelect('sum(s.rebateSportAmount) as rebate_sport_amount');
        $qb->addSelect('sum(s.rebateSportCount) as rebate_sport_count');
        $qb->addSelect('sum(s.rebateProbAmount) as rebate_prob_amount');
        $qb->addSelect('sum(s.rebateProbCount) as rebate_prob_count');
        $qb->addSelect('sum(s.rebateLotteryAmount) as rebate_lottery_amount');
        $qb->addSelect('sum(s.rebateLotteryCount) as rebate_lottery_count');
        $qb->addSelect('sum(s.rebateBBplayAmount) as rebate_bbplay_amount');
        $qb->addSelect('sum(s.rebateBBplayCount) as rebate_bbplay_count');
        $qb->addSelect('sum(s.rebateOfferAmount) as rebate_offer_amount');
        $qb->addSelect('sum(s.rebateOfferCount) as rebate_offer_count');
        $qb->addSelect('sum(s.rebateBBVideoAmount) as rebate_bbvideo_amount');
        $qb->addSelect('sum(s.rebateBBVideoCount) as rebate_bbvideo_count');
        $qb->addSelect('sum(s.rebateTTVideoAmount) as rebate_ttvideo_amount');
        $qb->addSelect('sum(s.rebateTTVideoCount) as rebate_ttvideo_count');
        $qb->addSelect('sum(s.rebateArmVideoAmount) as rebate_armvideo_amount');
        $qb->addSelect('sum(s.rebateArmVideoCount) as rebate_armvideo_count');
        $qb->addSelect('sum(s.rebateXpjVideoAmount) as rebate_xpjvideo_amount');
        $qb->addSelect('sum(s.rebateXpjVideoCount) as rebate_xpjvideo_count');
        $qb->addSelect('sum(s.rebateYfVideoAmount) as rebate_yfvideo_amount');
        $qb->addSelect('sum(s.rebateYfVideoCount) as rebate_yfvideo_count');
        $qb->addSelect('sum(s.rebate3dAmount) as rebate_3d_amount');
        $qb->addSelect('sum(s.rebate3dCount) as rebate_3d_count');
        $qb->addSelect('sum(s.rebateBattleAmount) as rebate_battle_amount');
        $qb->addSelect('sum(s.rebateBattleCount) as rebate_battle_count');
        $qb->addSelect('sum(s.rebateVirtualAmount) as rebate_virtual_amount');
        $qb->addSelect('sum(s.rebateVirtualCount) as rebate_virtual_count');
        $qb->addSelect('sum(s.rebateVipVideoAmount) as rebate_vipvideo_amount');
        $qb->addSelect('sum(s.rebateVipVideoCount) as rebate_vipvideo_count');
        $qb->addSelect('sum(s.rebateAgVideoAmount) as rebate_agvideo_amount');
        $qb->addSelect('sum(s.rebateAgVideoCount) as rebate_agvideo_count');
        $qb->addSelect('sum(s.rebatePTAmount) as rebate_pt_amount');
        $qb->addSelect('sum(s.rebatePTCount) as rebate_pt_count');
        $qb->addSelect('sum(s.rebateLTAmount) as rebate_lt_amount');
        $qb->addSelect('sum(s.rebateLTCount) as rebate_lt_count');
        $qb->addSelect('sum(s.rebateMIAmount) as rebate_mi_amount');
        $qb->addSelect('sum(s.rebateMICount) as rebate_mi_count');
        $qb->addSelect('sum(s.rebateABAmount) as rebate_ab_amount');
        $qb->addSelect('sum(s.rebateABCount) as rebate_ab_count');
        $qb->addSelect('sum(s.rebateMGAmount) as rebate_mg_amount');
        $qb->addSelect('sum(s.rebateMGCount) as rebate_mg_count');
        $qb->addSelect('sum(s.rebateOGAmount) as rebate_og_amount');
        $qb->addSelect('sum(s.rebateOGCount) as rebate_og_count');
        $qb->addSelect('sum(s.rebateSBAmount) as rebate_sb_amount');
        $qb->addSelect('sum(s.rebateSBCount) as rebate_sb_count');
        $qb->addSelect('sum(s.rebateGDAmount) as rebate_gd_amount');
        $qb->addSelect('sum(s.rebateGDCount) as rebate_gd_count');
        $qb->addSelect('sum(s.rebateSAAmount) as rebate_sa_amount');
        $qb->addSelect('sum(s.rebateSACount) as rebate_sa_count');
        $qb->addSelect('sum(s.rebateGnsAmount) as rebate_gns_amount');
        $qb->addSelect('sum(s.rebateGnsCount) as rebate_gns_count');
        $qb->addSelect('sum(s.rebateMGJackpotAmount) as rebate_mg_jackpot_amount');
        $qb->addSelect('sum(s.rebateMGJackpotCount) as rebate_mg_jackpot_count');
        $qb->addSelect('sum(s.rebateMGSlotsAmount) as rebate_mg_slots_amount');
        $qb->addSelect('sum(s.rebateMGSlotsCount) as rebate_mg_slots_count');
        $qb->addSelect('sum(s.rebateMGFeatureAmount) as rebate_mg_feature_amount');
        $qb->addSelect('sum(s.rebateMGFeatureCount) as rebate_mg_feature_count');
        $qb->addSelect('sum(s.rebateMGTableAmount) as rebate_mg_table_amount');
        $qb->addSelect('sum(s.rebateMGTableCount) as rebate_mg_table_count');
        $qb->addSelect('sum(s.rebateMGMobileAmount) as rebate_mg_mobile_amount');
        $qb->addSelect('sum(s.rebateMGMobileCount) as rebate_mg_mobile_count');
        $qb->addSelect('sum(s.rebateISBAmount) as rebate_isb_amount');
        $qb->addSelect('sum(s.rebateISBCount) as rebate_isb_count');
        $qb->addSelect('sum(s.rebateBBFGAmount) as rebate_bbfg_amount');
        $qb->addSelect('sum(s.rebateBBFGCount) as rebate_bbfg_count');
        $qb->addSelect('sum(s.rebateBCAmount) as rebate_bc_amount');
        $qb->addSelect('sum(s.rebateBCCount) as rebate_bc_count');
        $qb->addSelect('sum(s.rebateBBSlotsAmount) as rebate_bb_slots_amount');
        $qb->addSelect('sum(s.rebateBBSlotsCount) as rebate_bb_slots_count');
        $qb->addSelect('sum(s.rebateBBTableAmount) as rebate_bb_table_amount');
        $qb->addSelect('sum(s.rebateBBTableCount) as rebate_bb_table_count');
        $qb->addSelect('sum(s.rebateBBArcadeAmount) as rebate_bb_arcade_amount');
        $qb->addSelect('sum(s.rebateBBArcadeCount) as rebate_bb_arcade_count');
        $qb->addSelect('sum(s.rebateBBScratchAmount) as rebate_bb_scratch_amount');
        $qb->addSelect('sum(s.rebateBBScratchCount) as rebate_bb_scratch_count');
        $qb->addSelect('sum(s.rebateBBFeatureAmount) as rebate_bb_feature_amount');
        $qb->addSelect('sum(s.rebateBBFeatureCount) as rebate_bb_feature_count');
        $qb->addSelect('sum(s.rebateBBTreasureAmount) as rebate_bb_treasure_amount');
        $qb->addSelect('sum(s.rebateBBTreasureCount) as rebate_bb_treasure_count');
        $qb->addSelect('sum(s.rebateISBSlotsAmount) as rebate_isb_slots_amount');
        $qb->addSelect('sum(s.rebateISBSlotsCount) as rebate_isb_slots_count');
        $qb->addSelect('sum(s.rebateISBTableAmount) as rebate_isb_table_amount');
        $qb->addSelect('sum(s.rebateISBTableCount) as rebate_isb_table_count');
        $qb->addSelect('sum(s.rebateISBJackpotAmount) as rebate_isb_jackpot_amount');
        $qb->addSelect('sum(s.rebateISBJackpotCount) as rebate_isb_jackpot_count');
        $qb->addSelect('sum(s.rebateISBPokerAmount) as rebate_isb_poker_amount');
        $qb->addSelect('sum(s.rebateISBPokerCount) as rebate_isb_poker_count');
        $qb->addSelect('sum(s.rebate888FishingAmount) as rebate_888_fishing_amount');
        $qb->addSelect('sum(s.rebate888FishingCount) as rebate_888_fishing_count');
        $qb->addSelect('sum(s.rebatePTSlotsAmount) as rebate_pt_slots_amount');
        $qb->addSelect('sum(s.rebatePTSlotsCount) as rebate_pt_slots_count');
        $qb->addSelect('sum(s.rebatePTTableAmount) as rebate_pt_table_amount');
        $qb->addSelect('sum(s.rebatePTTableCount) as rebate_pt_table_count');
        $qb->addSelect('sum(s.rebatePTJackpotAmount) as rebate_pt_jackpot_amount');
        $qb->addSelect('sum(s.rebatePTJackpotCount) as rebate_pt_jackpot_count');
        $qb->addSelect('sum(s.rebatePTArcadeAmount) as rebate_pt_arcade_amount');
        $qb->addSelect('sum(s.rebatePTArcadeCount) as rebate_pt_arcade_count');
        $qb->addSelect('sum(s.rebatePTScratchAmount) as rebate_pt_scratch_amount');
        $qb->addSelect('sum(s.rebatePTScratchCount) as rebate_pt_scratch_count');
        $qb->addSelect('sum(s.rebatePTPokerAmount) as rebate_pt_poker_amount');
        $qb->addSelect('sum(s.rebatePTPokerCount) as rebate_pt_poker_count');
        $qb->addSelect('sum(s.rebatePTUnclassifiedAmount) as rebate_pt_unclassified_amount');
        $qb->addSelect('sum(s.rebatePTUnclassifiedCount) as rebate_pt_unclassified_count');
        $qb->addSelect('sum(s.rebateGOGAmount) as rebate_gog_amount');
        $qb->addSelect('sum(s.rebateGOGCount) as rebate_gog_count');
        $qb->addSelect('sum(s.rebateSk1Amount) as rebate_sk_1_amount');
        $qb->addSelect('sum(s.rebateSk1Count) as rebate_sk_1_count');
        $qb->addSelect('sum(s.rebateSk2Amount) as rebate_sk_2_amount');
        $qb->addSelect('sum(s.rebateSk2Count) as rebate_sk_2_count');
        $qb->addSelect('sum(s.rebateSk3Amount) as rebate_sk_3_amount');
        $qb->addSelect('sum(s.rebateSk3Count) as rebate_sk_3_count');
        $qb->addSelect('sum(s.rebateSk4Amount) as rebate_sk_4_amount');
        $qb->addSelect('sum(s.rebateSk4Count) as rebate_sk_4_count');
        $qb->addSelect('sum(s.rebateSk5Amount) as rebate_sk_5_amount');
        $qb->addSelect('sum(s.rebateSk5Count) as rebate_sk_5_count');
        $qb->addSelect('sum(s.rebateSk6Amount) as rebate_sk_6_amount');
        $qb->addSelect('sum(s.rebateSk6Count) as rebate_sk_6_count');
        $qb->addSelect('sum(s.rebateHBSlotsAmount) as rebate_hb_slots_amount');
        $qb->addSelect('sum(s.rebateHBSlotsCount) as rebate_hb_slots_count');
        $qb->addSelect('sum(s.rebateHBTableAmount) as rebate_hb_table_amount');
        $qb->addSelect('sum(s.rebateHBTableCount) as rebate_hb_table_count');
        $qb->addSelect('sum(s.rebateHBPokerAmount) as rebate_hb_poker_amount');
        $qb->addSelect('sum(s.rebateHBPokerCount) as rebate_hb_poker_count');
        $qb->addSelect('sum(s.rebateBGLiveAmount) as rebate_bg_live_amount');
        $qb->addSelect('sum(s.rebateBGLiveCount) as rebate_bg_live_count');
        $qb->addSelect('sum(s.rebateFishingMasterAmount) as rebate_fishing_master_amount');
        $qb->addSelect('sum(s.rebateFishingMasterCount) as rebate_fishing_master_count');
        $qb->addSelect('sum(s.rebatePPSlotsAmount) as rebate_pp_slots_amount');
        $qb->addSelect('sum(s.rebatePPSlotsCount) as rebate_pp_slots_count');
        $qb->addSelect('sum(s.rebatePPTableAmount) as rebate_pp_table_amount');
        $qb->addSelect('sum(s.rebatePPTableCount) as rebate_pp_table_count');
        $qb->addSelect('sum(s.rebatePPJackpotAmount) as rebate_pp_jackpot_amount');
        $qb->addSelect('sum(s.rebatePPJackpotCount) as rebate_pp_jackpot_count');
        $qb->addSelect('sum(s.rebatePPFeatureAmount) as rebate_pp_feature_amount');
        $qb->addSelect('sum(s.rebatePPFeatureCount) as rebate_pp_feature_count');
        $qb->addSelect('sum(s.rebatePTFishingAmount) as rebate_pt_fishing_amount');
        $qb->addSelect('sum(s.rebatePTFishingCount) as rebate_pt_fishing_count');
        $qb->addSelect('sum(s.rebateGNSSlotsAmount) as rebate_gns_slots_amount');
        $qb->addSelect('sum(s.rebateGNSSlotsCount) as rebate_gns_slots_count');
        $qb->addSelect('sum(s.rebateGNSFishingAmount) as rebate_gns_fishing_amount');
        $qb->addSelect('sum(s.rebateGNSFishingCount) as rebate_gns_fishing_count');
        $qb->addSelect('sum(s.rebateJDBSlotsAmount) as rebate_jdb_slots_amount');
        $qb->addSelect('sum(s.rebateJDBSlotsCount) as rebate_jdb_slots_count');
        $qb->addSelect('sum(s.rebateJDBArcadeAmount) as rebate_jdb_arcade_amount');
        $qb->addSelect('sum(s.rebateJDBArcadeCount) as rebate_jdb_arcade_count');
        $qb->addSelect('sum(s.rebateJDBFishingAmount) as rebate_jdb_fishing_amount');
        $qb->addSelect('sum(s.rebateJDBFishingCount) as rebate_jdb_fishing_count');
        $qb->addSelect('sum(s.rebateAgslotSlotsAmount) as rebate_agslot_slots_amount');
        $qb->addSelect('sum(s.rebateAgslotSlotsCount) as rebate_agslot_slots_count');
        $qb->addSelect('sum(s.rebateAgslotTableAmount) as rebate_agslot_table_amount');
        $qb->addSelect('sum(s.rebateAgslotTableCount) as rebate_agslot_table_count');
        $qb->addSelect('sum(s.rebateAgslotJackpotAmount) as rebate_agslot_jackpot_amount');
        $qb->addSelect('sum(s.rebateAgslotJackpotCount) as rebate_agslot_jackpot_count');
        $qb->addSelect('sum(s.rebateAgslotFishingAmount) as rebate_agslot_fishing_amount');
        $qb->addSelect('sum(s.rebateAgslotFishingCount) as rebate_agslot_fishing_count');
        $qb->addSelect('sum(s.rebateAgslotPokerAmount) as rebate_agslot_poker_amount');
        $qb->addSelect('sum(s.rebateAgslotPokerCount) as rebate_agslot_poker_count');
        $qb->addSelect('sum(s.rebateMWSlotsAmount) as rebate_mw_slots_amount');
        $qb->addSelect('sum(s.rebateMWSlotsCount) as rebate_mw_slots_count');
        $qb->addSelect('sum(s.rebateMWTableAmount) as rebate_mw_table_amount');
        $qb->addSelect('sum(s.rebateMWTableCount) as rebate_mw_table_count');
        $qb->addSelect('sum(s.rebateMWArcadeAmount) as rebate_mw_arcade_amount');
        $qb->addSelect('sum(s.rebateMWArcadeCount) as rebate_mw_arcade_count');
        $qb->addSelect('sum(s.rebateMWFishingAmount) as rebate_mw_fishing_amount');
        $qb->addSelect('sum(s.rebateMWFishingCount) as rebate_mw_fishing_count');
        $qb->addSelect('sum(s.rebateINSportAmount) as rebate_in_sport_amount');
        $qb->addSelect('sum(s.rebateINSportCount) as rebate_in_sport_count');
        $qb->addSelect('sum(s.rebateRTSlotsAmount) as rebate_rt_slots_amount');
        $qb->addSelect('sum(s.rebateRTSlotsCount) as rebate_rt_slots_count');
        $qb->addSelect('sum(s.rebateRTTableAmount) as rebate_rt_table_amount');
        $qb->addSelect('sum(s.rebateRTTableCount) as rebate_rt_table_count');
        $qb->addSelect('sum(s.rebateSGSlotsAmount) as rebate_sg_slots_amount');
        $qb->addSelect('sum(s.rebateSGSlotsCount) as rebate_sg_slots_count');
        $qb->addSelect('sum(s.rebateSGTableAmount) as rebate_sg_table_amount');
        $qb->addSelect('sum(s.rebateSGTableCount) as rebate_sg_table_count');
        $qb->addSelect('sum(s.rebateSGJackpotAmount) as rebate_sg_jackpot_amount');
        $qb->addSelect('sum(s.rebateSGJackpotCount) as rebate_sg_jackpot_count');
        $qb->addSelect('sum(s.rebateSGArcadeAmount) as rebate_sg_arcade_amount');
        $qb->addSelect('sum(s.rebateSGArcadeCount) as rebate_sg_arcade_count');
        $qb->addSelect('sum(s.rebateVRVrAmount) as rebate_vr_vr_amount');
        $qb->addSelect('sum(s.rebateVRVrCount) as rebate_vr_vr_count');
        $qb->addSelect('sum(s.rebateVRLottoAmount) as rebate_vr_lotto_amount');
        $qb->addSelect('sum(s.rebateVRLottoCount) as rebate_vr_lotto_count');
        $qb->addSelect('sum(s.rebateVRMarksixAmount) as rebate_vr_marksix_amount');
        $qb->addSelect('sum(s.rebateVRMarksixCount) as rebate_vr_marksix_count');
        $qb->addSelect('sum(s.rebatePT2SlotsAmount) as rebate_pt2_slots_amount');
        $qb->addSelect('sum(s.rebatePT2SlotsCount) as rebate_pt2_slots_count');
        $qb->addSelect('sum(s.rebatePT2JackpotAmount) as rebate_pt2_jackpot_amount');
        $qb->addSelect('sum(s.rebatePT2JackpotCount) as rebate_pt2_jackpot_count');
        $qb->addSelect('sum(s.rebatePT2FishingAmount) as rebate_pt2_fishing_amount');
        $qb->addSelect('sum(s.rebatePT2FishingCount) as rebate_pt2_fishing_count');
        $qb->addSelect('sum(s.rebatePT2TableAmount) as rebate_pt2_table_amount');
        $qb->addSelect('sum(s.rebatePT2TableCount) as rebate_pt2_table_count');
        $qb->addSelect('sum(s.rebatePT2FeatureAmount) as rebate_pt2_feature_amount');
        $qb->addSelect('sum(s.rebatePT2FeatureCount) as rebate_pt2_feature_count');
        $qb->addSelect('sum(s.rebateBngSlotsAmount) as rebate_bng_slots_amount');
        $qb->addSelect('sum(s.rebateBngSlotsCount) as rebate_bng_slots_count');
        $qb->addSelect('sum(s.rebateEVOAmount) as rebate_evo_amount');
        $qb->addSelect('sum(s.rebateEVOCount) as rebate_evo_count');
        $qb->addSelect('sum(s.rebateGNSJackpotAmount) as rebate_gns_jackpot_amount');
        $qb->addSelect('sum(s.rebateGNSJackpotCount) as rebate_gns_jackpot_count');
        $qb->addSelect('sum(s.rebateGNSFeatureAmount) as rebate_gns_feature_amount');
        $qb->addSelect('sum(s.rebateGNSFeatureCount) as rebate_gns_feature_count');
        $qb->addSelect('sum(s.rebateKYAmount) as rebate_ky_amount');
        $qb->addSelect('sum(s.rebateKYCount) as rebate_ky_count');
        $qb->addSelect('sum(s.rebateGNSTableGamesAmount) as rebate_gns_table_amount');
        $qb->addSelect('sum(s.rebateGNSTableGamesCount) as rebate_gns_table_count');
        $qb->groupBy('s.parentId');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳有返點統計記錄的代理數
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return integer
     */
    public function countNumOfRebateByParentId($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, [], $searchSet);

        $qb->select('COUNT(s.parentId)');
        $qb->groupBy('s.parentId');

        return count($qb->getQuery()->getArrayResult());
    }
}
