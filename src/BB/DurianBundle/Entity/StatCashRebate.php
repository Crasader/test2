<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 現金返點統計
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\StatCashRebateRepository")
 * @ORM\Table(
 *     name="stat_cash_rebate",
 *     indexes = {
 *         @ORM\Index(name = "idx_stat_cash_rebate_at_user_id", columns = {"at", "user_id"}),
 *         @ORM\Index(name = "idx_stat_cash_rebate_domain_at", columns = {"domain", "at"})
 *     }
 * )
 *
 * @author Sweet 2014.11.13
 */
class StatCashRebate
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer", options={"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 統計日期
     *
     * @var \DateTime
     *
     * @ORM\Column(name="at", type="datetime")
     */
    private $at;

    /**
     * 使用者編號
     *
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Column(name="currency", type="smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 廳
     *
     * @var integer
     *
     * @ORM\Column(name="domain", type="integer")
     */
    private $domain;

    /**
     * 上層ID
     *
     * @var integer
     *
     * @ORM\Column(name="parent_id", type="integer")
     */
    private $parentId;

    /**
     * 球類返點金額 opcode 1024
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_ball_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateBallAmount;

    /**
     * 球類返點次數 opcode 1024
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_ball_count", type = "integer")
     */
    private $rebateBallCount;

    /**
     * KENO返點金額 opcode 1025
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_keno_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateKenoAmount;

    /**
     * KENO返點次數 opcode 1025
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_keno_count", type = "integer")
     */
    private $rebateKenoCount;

    /**
     * 視訊返點金額 opcode 1026
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_video_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateVideoAmount;

    /**
     * 視訊返點次數 opcode 1026
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_video_count", type = "integer")
     */
    private $rebateVideoCount;

    /**
     * 體育返點金額 opcode 1027
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_sport_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateSportAmount;

    /**
     * 體育返點次數 opcode 1027
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_sport_count", type = "integer")
     */
    private $rebateSportCount;

    /**
     * 機率返點金額 opcode 1028
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_prob_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateProbAmount;

    /**
     * 機率返點次數 opcode 1028
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_prob_count", type = "integer")
     */
    private $rebateProbCount;

    /**
     * 彩票返點金額 opcode 1048
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_lottery_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateLotteryAmount;

    /**
     * 彩票返點次數 opcode 1048
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_lottery_count", type = "integer")
     */
    private $rebateLotteryCount;

    /**
     * BBplay返點金額 opcode 1050
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_bbplay_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateBBplayAmount;

    /**
     * BBplay返點次數 opcode 1050
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_bbplay_count", type = "integer")
     */
    private $rebateBBplayCount;

    /**
     * 優惠返點金額 opcode 1054
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_offer_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateOfferAmount;

    /**
     * 優惠返點次數 opcode 1054
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_offer_count", type = "integer")
     */
    private $rebateOfferCount;

    /**
     * BB視訊返點金額 opcode 1055
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_bbvideo_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateBBVideoAmount;

    /**
     * BB視訊返點次數 opcode 1055
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_bbvideo_count", type = "integer")
     */
    private $rebateBBVideoCount;

    /**
     * TT視訊返點金額 opcode 1057
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_ttvideo_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateTTVideoAmount;

    /**
     * TT視訊返點次數 opcode 1057
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_ttvideo_count", type = "integer")
     */
    private $rebateTTVideoCount;

    /**
     * 金臂視訊返點金額 opcode 1059
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_armvideo_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateArmVideoAmount;

    /**
     * 金臂視訊返點次數 opcode 1059
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_armvideo_count", type = "integer")
     */
    private $rebateArmVideoCount;

    /**
     * 新葡京視訊返點金額 opcode 1061
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_xpjvideo_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateXpjVideoAmount;

    /**
     * 新葡京視訊返點次數 opcode 1061
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_xpjvideo_count", type = "integer")
     */
    private $rebateXpjVideoCount;

    /**
     * 盈豐視訊返點金額 opcode 1063
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_yfvideo_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateYfVideoAmount;

    /**
     * 盈豐視訊返點次數 opcode 1063
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_yfvideo_count", type = "integer")
     */
    private $rebateYfVideoCount;

    /**
     * 3D廳返點金額 opcode 1065
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_3d_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebate3dAmount;

    /**
     * 3D廳返點次數 opcode 1065
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_3d_count", type = "integer")
     */
    private $rebate3dCount;

    /**
     * 對戰返點金額 opcode 1067
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_battle_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateBattleAmount;

    /**
     * 對戰返點次數 opcode 1067
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_battle_count", type = "integer")
     */
    private $rebateBattleCount;

    /**
     * 虛擬賽事返點金額 opcode 1069
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_virtual_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateVirtualAmount;

    /**
     * 虛擬賽事返點次數 opcode 1069
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_virtual_count", type = "integer")
     */
    private $rebateVirtualCount;

    /**
     * VIP視訊返點金額 opcode 1071
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_vipvideo_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateVipVideoAmount;

    /**
     * VIP視訊返點次數 opcode 1071
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_vipvideo_count", type = "integer")
     */
    private $rebateVipVideoCount;

    /**
     * AG視訊返點金額 opcode 1082
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_agvideo_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateAgVideoAmount;

    /**
     * AG視訊返點次數 opcode 1082
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_agvideo_count", type = "integer")
     */
    private $rebateAgVideoCount;

    /**
     * 返點總金額
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateAmount;

    /**
     * 返點總次數
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_count", type = "integer")
     */
    private $rebateCount;

    /**
     * PT電子遊藝返點金額 opcode 1091
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_pt_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebatePTAmount;

    /**
     * PT電子遊藝返點次數 opcode 1091
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_pt_count", type = "integer")
     */
    private $rebatePTCount;

    /**
     * LT返點金額 opcode 1093
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_lt_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateLTAmount;

    /**
     * LT返點次數 opcode 1093
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_lt_count", type = "integer")
     */
    private $rebateLTCount;

    /**
     * 競咪返點金額 opcode 1096
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_mi_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateMIAmount;

    /**
     * 競咪返點次數 opcode 1096
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_mi_count", type = "integer")
     */
    private $rebateMICount;

    /**
     * 歐博視訊返點金額 opcode 1108
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_ab_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateABAmount;

    /**
     * 歐博視訊返點次數 opcode 1108
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_ab_count", type = "integer")
     */
    private $rebateABCount;

    /**
     * MG電子返點金額 opcode 1116
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_mg_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateMGAmount;

    /**
     * MG電子返點次數 opcode 1116
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_mg_count", type = "integer")
     */
    private $rebateMGCount;

    /**
     * 東方視訊返點金額 opcode 1124
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_og_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateOGAmount;

    /**
     * 東方視訊返點次數 opcode 1124
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_og_count", type = "integer")
     */
    private $rebateOGCount;

    /**
     * SB體育返點金額 opcode 1135
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_sb_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateSBAmount;

    /**
     * SB體育返點次數 opcode 1135
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_sb_count", type = "integer")
     */
    private $rebateSBCount;

    /**
     * GD視訊返點金額 opcode 1143
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_gd_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateGDAmount;

    /**
     * GD視訊返點次數 opcode 1143
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_gd_count", type = "integer")
     */
    private $rebateGDCount;

    /**
     * 沙龍視訊返點金額 opcode 1155
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_sa_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateSAAmount;

    /**
     * 沙龍視訊返點次數 opcode 1155
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_sa_count", type = "integer")
     */
    private $rebateSACount;

    /**
     * Gns 機率返點金額 opcode 1165
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_gns_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateGnsAmount;

    /**
     * Gns 機率返點次數 opcode 1165
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_gns_count", type = "integer")
     */
    private $rebateGnsCount;

    /**
     * MG累積彩池返點金額 opcode 1168
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_mg_jackpot_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateMGJackpotAmount;

    /**
     * MG累積彩池返點次數 opcode 1168
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_mg_jackpot_count", type = "integer")
     */
    private $rebateMGJackpotCount;

    /**
     * MG老虎機返點金額 opcode 1169
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_mg_slots_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateMGSlotsAmount;

    /**
     * MG老虎機返點次數 opcode 1169
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_mg_slots_count", type = "integer")
     */
    private $rebateMGSlotsCount;

    /**
     * MG特色遊戲返點金額 opcode 1170
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_mg_feature_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateMGFeatureAmount;

    /**
     * MG特色遊戲返點次數 opcode 1170
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_mg_feature_count", type = "integer")
     */
    private $rebateMGFeatureCount;

    /**
     * MG桌上遊戲返點金額 opcode 1171
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_mg_table_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateMGTableAmount;

    /**
     * MG桌上遊戲返點次數 opcode 1171
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_mg_table_count", type = "integer")
     */
    private $rebateMGTableCount;

    /**
     * MG手機遊戲返點金額 opcode 1172
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_mg_mobile_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateMGMobileAmount;

    /**
     * MG手機遊戲返點次數 opcode 1172
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_mg_mobile_count", type = "integer")
     */
    private $rebateMGMobileCount;

    /**
     * ISB 電子返點金額 opcode 1185
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_isb_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateISBAmount;

    /**
     * ISB 電子返點次數 opcode 1185
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_isb_count", type = "integer")
     */
    private $rebateISBCount;

    /**
     * BB 捕魚達人返點金額 opcode 1189
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_bbfg_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateBBFGAmount;

    /**
     * BB 捕魚達人返點次數 opcode 1189
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_bbfg_count", type = "integer")
     */
    private $rebateBBFGCount;

    /**
     * BC 體育返點金額 opcode 1191
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_bc_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateBCAmount;

    /**
     * BC 體育返點次數 opcode 1191
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_bc_count", type = "integer")
     */
    private $rebateBCCount;

    /**
     * BB老虎機返點金額 opcode 1193
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_bb_slots_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateBBSlotsAmount;

    /**
     * BB老虎機返點次數 opcode 1193
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_bb_slots_count", type = "integer")
     */
    private $rebateBBSlotsCount;

    /**
     * BB桌上遊戲返點金額 opcode 1194
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_bb_table_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateBBTableAmount;

    /**
     * BB桌上遊戲返點次數 opcode 1194
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_bb_table_count", type = "integer")
     */
    private $rebateBBTableCount;

    /**
     * BB大型機台返點金額 opcode 1195
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_bb_arcade_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateBBArcadeAmount;

    /**
     * BB大型機台返點次數 opcode 1195
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_bb_arcade_count", type = "integer")
     */
    private $rebateBBArcadeCount;

    /**
     * BB刮刮樂返點金額 opcode 1196
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_bb_scratch_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateBBScratchAmount;

    /**
     * BB刮刮樂返點次數 opcode 1196
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_bb_scratch_count", type = "integer")
     */
    private $rebateBBScratchCount;

    /**
     * BB特色遊戲返點金額 opcode 1197
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_bb_feature_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateBBFeatureAmount;

    /**
     * BB特色遊戲返點次數 opcode 1197
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_bb_feature_count", type = "integer")
     */
    private $rebateBBFeatureCount;

    /**
     * 一元奪寶返點金額 opcode 1203
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_bb_treasure_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateBBTreasureAmount;

    /**
     * 一元奪寶返點次數 opcode 1203
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_bb_treasure_count", type = "integer")
     */
    private $rebateBBTreasureCount;

    /**
     * ISB老虎機返點金額 opcode 1205
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_isb_slots_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateISBSlotsAmount;

    /**
     * ISB老虎機返點次數 opcode 1205
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_isb_slots_count", type = "integer")
     */
    private $rebateISBSlotsCount;

    /**
     * ISB桌上遊戲返點金額 opcode 1206
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_isb_table_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateISBTableAmount;

    /**
     * ISB桌上遊戲返點次數 opcode 1206
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_isb_table_count", type = "integer")
     */
    private $rebateISBTableCount;

    /**
     * ISB累積彩池返點金額 opcode 1207
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_isb_jackpot_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateISBJackpotAmount;

    /**
     * ISB累積彩池返點次數 opcode 1207
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_isb_jackpot_count", type = "integer")
     */
    private $rebateISBJackpotCount;

    /**
     * ISB視訊撲克返點金額 opcode 1208
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_isb_poker_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateISBPokerAmount;

    /**
     * ISB視訊撲克返點次數 opcode 1208
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_isb_poker_count", type = "integer")
     */
    private $rebateISBPokerCount;

    /**
     * 888捕魚返點金額 opcode 1220
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_888_fishing_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebate888FishingAmount;

    /**
     * 888捕魚返點次數 opcode 1220
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_888_fishing_count", type = "integer")
     */
    private $rebate888FishingCount;

    /**
     * PT老虎機返點金額 opcode 1224
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_pt_slots_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebatePTSlotsAmount;

    /**
     * PT老虎機返點次數 opcode 1224
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_pt_slots_count", type = "integer")
     */
    private $rebatePTSlotsCount;

    /**
     * PT桌上遊戲返點金額 opcode 1225
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_pt_table_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebatePTTableAmount;

    /**
     * PT桌上遊戲返點次數 opcode 1225
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_pt_table_count", type = "integer")
     */
    private $rebatePTTableCount;

    /**
     * PT累積彩池返點金額 opcode 1226
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_pt_jackpot_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebatePTJackpotAmount;

    /**
     * PT累積彩池返點次數 opcode 1226
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_pt_jackpot_count", type = "integer")
     */
    private $rebatePTJackpotCount;

    /**
     * PT大型機台返點金額 opcode 1227
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_pt_arcade_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebatePTArcadeAmount;

    /**
     * PT大型機台返點次數 opcode 1227
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_pt_arcade_count", type = "integer")
     */
    private $rebatePTArcadeCount;

    /**
     * PT刮刮樂返點金額 opcode 1228
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_pt_scratch_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebatePTScratchAmount;

    /**
     * PT刮刮樂返點次數 opcode 1228
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_pt_scratch_count", type = "integer")
     */
    private $rebatePTScratchCount;

    /**
     * PT視訊撲克返點金額 opcode 1229
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_pt_poker_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebatePTPokerAmount;

    /**
     * PT視訊撲克返點次數 opcode 1229
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_pt_poker_count", type = "integer")
     */
    private $rebatePTPokerCount;

    /**
     * PT未分類返點金額 opcode 1236
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_pt_unclassified_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebatePTUnclassifiedAmount;

    /**
     * PT未分類返點次數 opcode 1236
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_pt_unclassified_count", type = "integer")
     */
    private $rebatePTUnclassifiedCount;

    /**
     * 賭神廳返點金額 opcode 1238
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_gog_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateGOGAmount;

    /**
     * 賭神廳返點次數 opcode 1238
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_gog_count", type = "integer")
     */
    private $rebateGOGCount;

    /**
     * 一般彩票返點金額 opcode 1240
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_sk_1_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateSk1Amount;

    /**
     * 一般彩票返點次數 opcode 1240
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_sk_1_count", type = "integer")
     */
    private $rebateSk1Count;

    /**
     * BB快開返點金額 opcode 1241
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_sk_2_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateSk2Amount;

    /**
     * BB快開返點次數 opcode 1241
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_sk_2_count", type = "integer")
     */
    private $rebateSk2Count;

    /**
     * PK&11選5返點金額 opcode 1242
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_sk_3_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateSk3Amount;

    /**
     * PK&11選5返點次數 opcode 1242
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_sk_3_count", type = "integer")
     */
    private $rebateSk3Count;

    /**
     * 時時彩&快3返點金額 opcode 1243
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_sk_4_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateSk4Amount;

    /**
     * 時時彩&快3返點次數 opcode 1243
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_sk_4_count", type = "integer")
     */
    private $rebateSk4Count;

    /**
     * Keno返點金額 opcode 1244
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_sk_5_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateSk5Amount;

    /**
     * Keno返點次數 opcode 1244
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_sk_5_count", type = "integer")
     */
    private $rebateSk5Count;

    /**
     * 十分彩返點金額 opcode 1245
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_sk_6_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateSk6Amount;

    /**
     * 十分彩返點次數 opcode 1245
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_sk_6_count", type = "integer")
     */
    private $rebateSk6Count;

    /**
     * HB老虎機返點金額 opcode 1260
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_hb_slots_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateHBSlotsAmount;

    /**
     * HB老虎機返點次數 opcode 1260
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_hb_slots_count", type = "integer")
     */
    private $rebateHBSlotsCount;

    /**
     * HB桌上遊戲返點金額 opcode 1261
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_hb_table_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateHBTableAmount;

    /**
     * HB桌上遊戲返點次數 opcode 1261
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_hb_table_count", type = "integer")
     */
    private $rebateHBTableCount;

    /**
     * HB視訊撲克返點金額 opcode 1262
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_hb_poker_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateHBPokerAmount;

    /**
     * HB視訊撲克返點次數 opcode 1262
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_hb_poker_count", type = "integer")
     */
    private $rebateHBPokerCount;

    /**
     * BG視訊返點金額 opcode 1274
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_bg_live_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateBGLiveAmount;

    /**
     * BG視訊返點次數 opcode 1274
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_bg_live_count", type = "integer")
     */
    private $rebateBGLiveCount;

    /**
     * BB捕魚大師返點金額 opcode 1284
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_fishing_master_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateFishingMasterAmount;

    /**
     * BB捕魚大師返點次數 opcode 1284
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_fishing_master_count", type = "integer")
     */
    private $rebateFishingMasterCount;

    /**
     * PP老虎機返點金額 opcode 1286
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_pp_slots_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebatePPSlotsAmount;

    /**
     * PP老虎機返點次數 opcode 1286
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_pp_slots_count", type = "integer")
     */
    private $rebatePPSlotsCount;

    /**
     * PP桌上遊戲返點金額 opcode 1287
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_pp_table_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebatePPTableAmount;

    /**
     * PP桌上遊戲返點次數 opcode 1287
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_pp_table_count", type = "integer")
     */
    private $rebatePPTableCount;

    /**
     * PP累積彩池返點金額 opcode 1288
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_pp_jackpot_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebatePPJackpotAmount;

    /**
     * PP累積彩池返點次數 opcode 1288
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_pp_jackpot_count", type = "integer")
     */
    private $rebatePPJackpotCount;

    /**
     * PP特色遊戲返點金額 opcode 1289
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_pp_feature_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebatePPFeatureAmount;

    /**
     * PP特色遊戲返點次數 opcode 1289
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_pp_feature_count", type = "integer")
     */
    private $rebatePPFeatureCount;

    /**
     * PT捕魚機返點金額 opcode 1318
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_pt_fishing_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebatePTFishingAmount;

    /**
     * PT捕魚機返點次數 opcode 1318
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_pt_fishing_count", type = "integer")
     */
    private $rebatePTFishingCount;

    /**
     * GNS老虎機返點金額 opcode 1320
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_gns_slots_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateGNSSlotsAmount;

    /**
     * GNS老虎機返點次數 opcode 1320
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_gns_slots_count", type = "integer")
     */
    private $rebateGNSSlotsCount;

    /**
     * GNS捕魚機返點金額 opcode 1322
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_gns_fishing_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateGNSFishingAmount;

    /**
     * GNS捕魚機返點次數 opcode 1322
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_gns_fishing_count", type = "integer")
     */
    private $rebateGNSFishingCount;

    /**
     * JDB老虎機返點金額 opcode 1324
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_jdb_slots_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateJDBSlotsAmount;

    /**
     * JDB老虎機返點次數 opcode 1324
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_jdb_slots_count", type = "integer")
     */
    private $rebateJDBSlotsCount;

    /**
     * JDB大型機台返點金額 opcode 1325
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_jdb_arcade_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateJDBArcadeAmount;

    /**
     * JDB大型機台返點次數 opcode 1325
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_jdb_arcade_count", type = "integer")
     */
    private $rebateJDBArcadeCount;

    /**
     * JDB捕魚機返點金額 opcode 1326
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_jdb_fishing_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateJDBFishingAmount;

    /**
     * JDB捕魚機返點次數 opcode 1326
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_jdb_fishing_count", type = "integer")
     */
    private $rebateJDBFishingCount;

    /**
     * AG老虎機返點金額 opcode 1330
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_agslot_slots_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateAgslotSlotsAmount;

    /**
     * AG老虎機返點次數 opcode 1330
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_agslot_slots_count", type = "integer")
     */
    private $rebateAgslotSlotsCount;

    /**
     * AG桌上遊戲返點金額 opcode 1331
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_agslot_table_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateAgslotTableAmount;

    /**
     * AG桌上遊戲返點次數 opcode 1331
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_agslot_table_count", type = "integer")
     */
    private $rebateAgslotTableCount;

    /**
     * AG累積彩池返點金額 opcode 1332
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_agslot_jackpot_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateAgslotJackpotAmount;

    /**
     * AG累積彩池返點次數 opcode 1332
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_agslot_jackpot_count", type = "integer")
     */
    private $rebateAgslotJackpotCount;

    /**
     * AG捕魚機返點金額 opcode 1333
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_agslot_fishing_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateAgslotFishingAmount;

    /**
     * AG捕魚機返點次數 opcode 1333
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_agslot_fishing_count", type = "integer")
     */
    private $rebateAgslotFishingCount;

    /**
     * AG視頻撲克返點金額 opcode 1334
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_agslot_poker_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateAgslotPokerAmount;

    /**
     * AG視頻撲克返點次數 opcode 1334
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_agslot_poker_count", type = "integer")
     */
    private $rebateAgslotPokerCount;

    /**
     * MW老虎機返點金額 opcode 1343
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_mw_slots_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateMWSlotsAmount;

    /**
     * MW老虎機返點次數 opcode 1343
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_mw_slots_count", type = "integer")
     */
    private $rebateMWSlotsCount;

    /**
     * MW桌上遊戲返點金額 opcode 1344
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_mw_table_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateMWTableAmount;

    /**
     * MW桌上遊戲返點次數 opcode 1344
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_mw_table_count", type = "integer")
     */
    private $rebateMWTableCount;

    /**
     * MW大型機台返點金額 opcode 1345
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_mw_arcade_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateMWArcadeAmount;

    /**
     * MW大型機台返點次數 opcode 1345
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_mw_arcade_count", type = "integer")
     */
    private $rebateMWArcadeCount;

    /**
     * MW捕魚機返點金額 opcode 1346
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_mw_fishing_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateMWFishingAmount;

    /**
     * MW捕魚機返點次數 opcode 1346
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_mw_fishing_count", type = "integer")
     */
    private $rebateMWFishingCount;

    /**
     * IN體育返點金額 opcode 1352
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_in_sport_amount", type = "decimal", precision = 16, scale = 4)
     */

    private $rebateINSportAmount;

    /**
     * IN體育返點次數 opcode 1352
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_in_sport_count", type = "integer")
     */
    private $rebateINSportCount;

    /**
     * RT老虎機返點金額 opcode 1379
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_rt_slots_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateRTSlotsAmount;

    /**
     * RT老虎機返點次數 opcode 1379
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_rt_slots_count", type = "integer")
     */
    private $rebateRTSlotsCount;

    /**
     * RT桌上遊戲返點金額 opcode 1380
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_rt_table_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateRTTableAmount;

    /**
     * RT桌上遊戲返點次數 opcode 1380
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_rt_table_count", type = "integer")
     */
    private $rebateRTTableCount;

    /**
     * SG老虎機返點金額 opcode 1383
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_sg_slots_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateSGSlotsAmount;

    /**
     * SG老虎機返點次數 opcode 1383
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_sg_slots_count", type = "integer")
     */
    private $rebateSGSlotsCount;

    /**
     * SG桌上遊戲返點金額 opcode 1384
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_sg_table_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateSGTableAmount;

    /**
     * SG桌上遊戲返點次數 opcode 1384
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_sg_table_count", type = "integer")
     */
    private $rebateSGTableCount;

    /**
     * SG累積彩池返點金額 opcode 1385
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_sg_jackpot_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateSGJackpotAmount;

    /**
     * SG累積彩池返點次數 opcode 1385
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_sg_jackpot_count", type = "integer")
     */
    private $rebateSGJackpotCount;

    /**
     * SG大型機台返點金額 opcode 1386
     *
     * @var float
     *
     * @ORM\Column(name = "rebate_sg_arcade_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateSGArcadeAmount;

    /**
     * SG大型機台返點次數 opcode 1386
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_sg_arcade_count", type = "integer")
     */
    private $rebateSGArcadeCount;

    /**
     * VR真人彩返點金額 opcode 1391
     *
     * @var float
     *
     * @ORM\Column(name="rebate_vr_vr_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateVRVrAmount;

    /**
     * VR真人彩返點次數 opcode 1391
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_vr_vr_count", type = "integer")
     */
    private $rebateVRVrCount;

    /**
     * VR國家彩返點金額 opcode 1392
     *
     * @var float
     *
     * @ORM\Column(name="rebate_vr_lotto_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateVRLottoAmount;

    /**
     * VR國家彩返點次數 opcode 1392
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_vr_lotto_count", type = "integer")
     */
    private $rebateVRLottoCount;

    /**
     * VR六合彩返點金額 opcode 1393
     *
     * @var float
     *
     * @ORM\Column(name="rebate_vr_marksix_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateVRMarksixAmount;

    /**
     * VR六合彩返點次數 opcode 1393
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_vr_marksix_count", type = "integer")
     */
    private $rebateVRMarksixCount;

    /**
     * PTⅡ老虎機返點金額 opcode 1421
     *
     * @var float
     *
     * @ORM\Column(name="rebate_pt2_slots_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebatePT2SlotsAmount;

    /**
     * PTⅡ老虎機返點次數 opcode 1421
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_pt2_slots_count", type = "integer")
     */
    private $rebatePT2SlotsCount;

    /**
     * PTⅡ累積彩池返點金額 opcode 1422
     *
     * @var float
     *
     * @ORM\Column(name="rebate_pt2_jackpot_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebatePT2JackpotAmount;

    /**
     * PTⅡ累積彩池返點次數 opcode 1422
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_pt2_jackpot_count", type = "integer")
     */
    private $rebatePT2JackpotCount;

    /**
     * PTⅡ捕魚機返點金額 opcode 1423
     *
     * @var float
     *
     * @ORM\Column(name="rebate_pt2_fishing_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebatePT2FishingAmount;

    /**
     * PTⅡ捕魚機返點次數 opcode 1423
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_pt2_fishing_count", type = "integer")
     */
    private $rebatePT2FishingCount;

    /**
     * PTⅡ桌上遊戲返點金額 opcode 1427
     *
     * @var float
     *
     * @ORM\Column(name="rebate_pt2_table_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebatePT2TableAmount;

    /**
     * PTⅡ桌上遊戲返點次數 opcode 1427
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_pt2_table_count", type = "integer")
     */
    private $rebatePT2TableCount;

    /**
     * PTⅡ特色遊戲返點金額 opcode 1428
     *
     * @var float
     *
     * @ORM\Column(name="rebate_pt2_feature_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebatePT2FeatureAmount;

    /**
     * PTⅡ特色遊戲返點次數 opcode 1428
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_pt2_feature_count", type = "integer")
     */
    private $rebatePT2FeatureCount;

    /**
     * BNG老虎機返點金額 opcode 1431
     *
     * @var float
     *
     * @ORM\Column(name="rebate_bng_slots_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateBngSlotsAmount;

    /**
     * BNG老虎機返點次數 opcode 1431
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_bng_slots_count", type = "integer")
     */
    private $rebateBngSlotsCount;

    /**
     * EVO視訊返點金額 opcode 1434
     *
     * @var float
     *
     * @ORM\Column(name="rebate_evo_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateEVOAmount;

    /**
     * EVO視訊返點次數 opcode 1434
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_evo_count", type = "integer")
     */
    private $rebateEVOCount;

    /**
     * GNS累積彩池返點金額 opcode 1436
     *
     * @var float
     *
     * @ORM\Column(name="rebate_gns_jackpot_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateGNSJackpotAmount;

    /**
     * GNS累積彩池返點次數 opcode 1436
     *
     * @var integer
     *
     * @ORM\Column(name="rebate_gns_jackpot_count", type = "integer")
     */
    private $rebateGNSJackpotCount;

    /**
     * GNS特色遊戲返點金額 opcode 1446
     *
     * @var float
     *
     * @ORM\Column(name="rebate_gns_feature_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateGNSFeatureAmount;

    /**
     * GNS特色遊戲返點次數 opcode 1446
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_gns_feature_count", type = "integer")
     */
    private $rebateGNSFeatureCount;

    /**
     * 開元棋牌返點金額 opcode 1448
     *
     * @var float
     *
     * @ORM\Column(name="rebate_ky_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateKYAmount;

    /**
     * 開元棋牌返點次數 opcode 1448
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_ky_count", type = "integer")
     */
    private $rebateKYCount;

    /**
     * GNS桌上遊戲返點金額 opcode 1450
     *
     * @var float
     *
     * @ORM\Column(name="rebate_gns_table_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $rebateGNSTableGamesAmount;

    /**
     * GNS桌上遊戲返點次數 opcode 1450
     *
     * @var integer
     *
     * @ORM\Column(name = "rebate_gns_table_count", type = "integer")
     */
    private $rebateGNSTableGamesCount;

    /**
     * 建構子
     *
     * @param \DateTime $at       統計日期
     * @param integer   $userId   使用者編號
     * @param integer   $currency 幣別
     */
    public function __construct($at, $userId, $currency)
    {
        $this->at = $at;
        $this->userId = $userId;
        $this->currency = $currency;
        $this->rebateBallAmount = 0; //球類返點金額 opcode 1024
        $this->rebateBallCount = 0; //球類返點次數 opcode 1024
        $this->rebateKenoAmount = 0; //KENO返點金額 opcode 1025
        $this->rebateKenoCount = 0; //KENO返點次數 opcode 1025
        $this->rebateVideoAmount = 0; //視訊返點金額 opcode 1026
        $this->rebateVideoCount = 0; //視訊返點次數 opcode 1026
        $this->rebateSportAmount = 0; //體育返點金額 opcode 1027
        $this->rebateSportCount = 0; //體育返點次數 opcode 1027
        $this->rebateProbAmount = 0; //機率返點金額 opcode 1028
        $this->rebateProbCount = 0; //機率返點次數 opcode 1028
        $this->rebateLotteryAmount = 0; //彩票返點金額 opcode 1048
        $this->rebateLotteryCount = 0; //彩票返點次數 opcode 1048
        $this->rebateBBplayAmount = 0; //BBplay返點金額 opcode 1050
        $this->rebateBBplayCount = 0; //BBplay返點次數 opcode 1050
        $this->rebateOfferAmount = 0; //優惠返點金額 opcode 1054
        $this->rebateOfferCount = 0; //優惠返點次數 opcode 1054
        $this->rebateBBVideoAmount = 0; //BB視訊返點金額 opcode 1055
        $this->rebateBBVideoCount = 0; //BB視訊返點次數 opcode 1055
        $this->rebateTTVideoAmount = 0; //TT視訊返點金額 opcode 1057
        $this->rebateTTVideoCount = 0; //TT視訊返點次數 opcode 1057
        $this->rebateArmVideoAmount = 0; //金臂視訊返點金額 opcode 1059
        $this->rebateArmVideoCount = 0; //金臂視訊返點次數 opcode 1059
        $this->rebateXpjVideoAmount = 0; //新葡京視訊返點金額 opcode 1061
        $this->rebateXpjVideoCount = 0; //新葡京視訊返點次數 opcode 1061
        $this->rebateYfVideoAmount = 0; //盈豐視訊返點金額 opcode 1063
        $this->rebateYfVideoCount = 0; //盈豐視訊返點次數 opcode 1063
        $this->rebate3dAmount = 0; //3D廳返點金額 opcode 1065
        $this->rebate3dCount = 0; //3D廳返點次數 opcode 1065
        $this->rebateBattleAmount = 0; //對戰返點金額 opcode 1067
        $this->rebateBattleCount = 0; //對戰返點次數 opcode 1067
        $this->rebateVirtualAmount = 0; //虛擬賽事返點金額 opcode 1069
        $this->rebateVirtualCount = 0; //虛擬賽事返點次數 opcode 1069
        $this->rebateVipVideoAmount = 0; //VIP視訊返點金額 opcode 1071
        $this->rebateVipVideoCount = 0; //VIP視訊返點次數 opcode 1071
        $this->rebateAgVideoAmount = 0; //AG視訊返點金額 opcode 1082
        $this->rebateAgVideoCount = 0; //AG視訊返點次數 opcode 1082
        $this->rebatePTAmount = 0; //PT電子遊藝返點金額 opcode 1091
        $this->rebatePTCount = 0; //PT電子遊藝返點次數 opcode 1091
        $this->rebateLTAmount = 0; //LT返點金額 opcode 1093
        $this->rebateLTCount = 0; //LT返點次數 opcode 1093
        $this->rebateMIAmount = 0; //競咪返點金額 opcode 1096
        $this->rebateMICount = 0; //競咪返點次數 opcode 1096
        $this->rebateABAmount = 0; //歐博視訊返點金額 opcode 1108
        $this->rebateABCount = 0; //歐博視訊返點次數 opcode 1108
        $this->rebateMGAmount = 0; //MG電子返點金額 opcode 1116
        $this->rebateMGCount = 0; //MG電子返點次數 opcode 1116
        $this->rebateOGAmount = 0; //東方視訊返點金額 opcode 1124
        $this->rebateOGCount = 0; //東方視訊返點次數 opcode 1124
        $this->rebateSBAmount = 0; //SB體育返點金額 opcode 1135
        $this->rebateSBCount = 0; //SB體育返點次數 opcode 1135
        $this->rebateGDAmount = 0; //GD視訊返點金額 opcode 1143
        $this->rebateGDCount = 0; //GD視訊返點次數 opcode 1143
        $this->rebateSAAmount = 0; //沙龍視訊返點金額 opcode 1155
        $this->rebateSACount = 0; //沙龍視訊返點次數 opcode 1155
        $this->rebateGnsAmount = 0; //Gns 機率返點金額 opcode 1165
        $this->rebateGnsCount = 0; //Gns 機率返點次數 opcode 1165
        $this->rebateMGJackpotAmount = 0; //MG累積彩池返點金額 opcode 1168
        $this->rebateMGJackpotCount = 0; //MG累積彩池返點次數 opcode 1168
        $this->rebateMGSlotsAmount = 0; //MG老虎機返點金額 opcode 1169
        $this->rebateMGSlotsCount = 0; //MG老虎機返點次數 opcode 1169
        $this->rebateMGFeatureAmount = 0; //MG特色遊戲返點金額 opcode 1170
        $this->rebateMGFeatureCount = 0; //MG特色遊戲返點次數 opcode 1170
        $this->rebateMGTableAmount = 0; //MG桌上遊戲返點金額 opcode 1171
        $this->rebateMGTableCount = 0; //MG桌上遊戲返點次數 opcode 1171
        $this->rebateMGMobileAmount = 0; //MG手機遊戲返點金額 opcode 1172
        $this->rebateMGMobileCount = 0; //MG手機遊戲返點次數 opcode 1172
        $this->rebateISBAmount = 0; //ISB 電子返點金額 opcode 1185
        $this->rebateISBCount = 0; //ISB 電子返點次數 opcode 1185
        $this->rebateBBFGAmount = 0; //BB 捕魚達人返點金額 opcode 1189
        $this->rebateBBFGCount = 0; //BB 捕魚達人返點次數 opcode 1189
        $this->rebateBCAmount = 0; //BC 體育返點金額 opcode 1191
        $this->rebateBCCount = 0; //BC 體育返點次數 opcode 1191
        $this->rebateBBSlotsAmount = 0; //BB老虎機返點金額 opcode 1193
        $this->rebateBBSlotsCount = 0; //BB老虎機返點次數 opcode 1193
        $this->rebateBBTableAmount = 0; //BB桌上遊戲返點金額 opcode 1194
        $this->rebateBBTableCount = 0; //BB桌上遊戲返點次數 opcode 1194
        $this->rebateBBArcadeAmount = 0; //BB大型機台返點金額 opcode 1195
        $this->rebateBBArcadeCount = 0; //BB大型機台返點次數 opcode 1195
        $this->rebateBBScratchAmount = 0; //BB刮刮樂返點金額 opcode 1196
        $this->rebateBBScratchCount = 0; //BB刮刮樂返點次數 opcode 1196
        $this->rebateBBFeatureAmount = 0; //BB特色遊戲返點金額 opcode 1197
        $this->rebateBBFeatureCount = 0; //BB特色遊戲返點次數 opcode 1197
        $this->rebateBBTreasureAmount = 0; //一元奪寶返點金額 opcode 1203
        $this->rebateBBTreasureCount = 0; //一元奪寶返點次數 opcode 1203
        $this->rebateISBSlotsAmount = 0; //ISB老虎機返點金額 opcode 1205
        $this->rebateISBSlotsCount = 0; //ISB老虎機返點次數 opcode 1205
        $this->rebateISBTableAmount = 0; //ISB桌上遊戲返點金額 opcode 1206
        $this->rebateISBTableCount = 0; //ISB桌上遊戲返點次數 opcode 1206
        $this->rebateISBJackpotAmount = 0; //ISB累積彩池返點金額 opcode 1207
        $this->rebateISBJackpotCount = 0; //ISB累積彩池返點次數 opcode 1207
        $this->rebateISBPokerAmount = 0; //ISB視訊撲克返點金額 opcode 1208
        $this->rebateISBPokerCount = 0; //ISB視訊撲克返點次數 opcode 1208
        $this->rebate888FishingAmount = 0; //888捕魚返點金額 opcode 1220
        $this->rebate888FishingCount = 0; //888捕魚返點次數 opcode 1220
        $this->rebatePTSlotsAmount = 0; //PT老虎機返點金額 opcode 1224
        $this->rebatePTSlotsCount = 0; //PT老虎機返點次數 opcode 1224
        $this->rebatePTTableAmount = 0; //PT桌上遊戲返點金額 opcode 1225
        $this->rebatePTTableCount = 0; //PT桌上遊戲返點次數 opcode 1225
        $this->rebatePTJackpotAmount = 0; //PT累積彩池返點金額 opcode 1226
        $this->rebatePTJackpotCount = 0; //PT累積彩池返點次數 opcode 1226
        $this->rebatePTArcadeAmount = 0; //PT大型機台返點金額 opcode 1227
        $this->rebatePTArcadeCount = 0; //PT大型機台返點次數 opcode 1227
        $this->rebatePTScratchAmount = 0; //PT刮刮樂返點金額 opcode 1228
        $this->rebatePTScratchCount = 0; //PT刮刮樂返點次數 opcode 1228
        $this->rebatePTPokerAmount = 0; //PT視訊撲克返點金額 opcode 1229
        $this->rebatePTPokerCount = 0; //PT視訊撲克返點次數 opcode 1229
        $this->rebatePTUnclassifiedAmount = 0; //PT未分類返點金額 opcode 1236
        $this->rebatePTUnclassifiedCount = 0; //PT未分類返點次數 opcode 1236
        $this->rebateGOGAmount = 0; //賭神廳返點總金額 opcode1238
        $this->rebateGOGCount = 0; //賭神廳返點總次數 opcode1238
        $this->rebateSk1Amount = 0; //一般彩票返點總金額 opcode1240
        $this->rebateSk1Count = 0; //一般彩票返點總次數 opcode1240
        $this->rebateSk2Amount = 0; //BB快開返點總金額 opcode1241
        $this->rebateSk2Count = 0; //BB快開返點總次數 opcode1241
        $this->rebateSk3Amount = 0; //PK&11選5返點總金額 opcode1242
        $this->rebateSk3Count = 0; //PK&11選5返點總次數 opcode1242
        $this->rebateSk4Amount = 0; //時時彩&快3返點總金額 opcode1243
        $this->rebateSk4Count = 0; //時時彩&快3返點總次數 opcode1243
        $this->rebateSk5Amount = 0; //Keno返點總金額 opcode1244
        $this->rebateSk5Count = 0; //Keno返點總次數 opcode1244
        $this->rebateSk6Amount = 0; //十分彩返點總金額 opcode1245
        $this->rebateSk6Count = 0; //十分彩返點總次數 opcode1245
        $this->rebateHBSlotsAmount = 0; //HB老虎機返點金額 opcode 1260
        $this->rebateHBSlotsCount = 0; //HB老虎機返點次數 opcode 1260
        $this->rebateHBTableAmount = 0; //HB桌上遊戲返點金額 opcode 1261
        $this->rebateHBTableCount = 0; //HB桌上遊戲返點次數 opcode 1261
        $this->rebateHBPokerAmount = 0; //HB視訊撲克返點金額 opcode 1262
        $this->rebateHBPokerCount = 0; //HB視訊撲克返點次數 opcode 1262
        $this->rebateBGLiveAmount = 0; //BG視訊返點金額 opcode 1274
        $this->rebateBGLiveCount = 0; //BG視訊返點次數 opcode 1274
        $this->rebateFishingMasterAmount = 0; //BB捕魚大師返點金額 opcode 1284
        $this->rebateFishingMasterCount = 0; //BB捕魚大師返點次數 opcode 1284
        $this->rebatePPSlotsAmount = 0; //PP老虎機返點金額 opcode 1286
        $this->rebatePPSlotsCount = 0; //PP老虎機返點次數 opcode 1286
        $this->rebatePPTableAmount = 0; //PP桌上遊戲返點金額 opcode 1287
        $this->rebatePPTableCount = 0; //PP桌上遊戲返點次數 opcode 1287
        $this->rebatePPJackpotAmount = 0; //PP累積彩池返點金額 opcode 1288
        $this->rebatePPJackpotCount = 0; //PP累積彩池返點次數 opcode 1288
        $this->rebatePPFeatureAmount = 0; //PP特色遊戲返點金額 opcode 1289
        $this->rebatePPFeatureCount = 0; //PP特色遊戲返點次數 opcode 1289
        $this->rebatePTFishingAmount = 0; //PT捕魚機返點金額 opcode 1318
        $this->rebatePTFishingCount = 0; //PT捕魚機返點次數 opcode 1318
        $this->rebateGNSSlotsAmount = 0; //GNS老虎機返點金額 opcode 1320
        $this->rebateGNSSlotsCount = 0; //GNS老虎機返點次數 opcode 1320
        $this->rebateGNSFishingAmount = 0; //GNS捕魚機返點金額 opcode 1322
        $this->rebateGNSFishingCount = 0; //GNS捕魚機返點次數 opcode 1322
        $this->rebateJDBSlotsAmount = 0; //JDB老虎機返點金額 opcode 1324
        $this->rebateJDBSlotsCount = 0; //JDB老虎機返點次數 opcode 1324
        $this->rebateJDBArcadeAmount = 0; //JDB大型機台返點金額 opcode 1325
        $this->rebateJDBArcadeCount = 0; //JDB大型機台返點次數 opcode 1325
        $this->rebateJDBFishingAmount = 0; //JDB捕魚機返點金額 opcode 1326
        $this->rebateJDBFishingCount = 0; //JDB捕魚機返點次數 opcode 1326
        $this->rebateAgslotSlotsAmount = 0; //AG老虎機返點金額 opcode 1330
        $this->rebateAgslotSlotsCount = 0; //AG老虎機返點次數 opcode 1330
        $this->rebateAgslotTableAmount = 0; //AG桌上遊戲返點金額 opcode 1331
        $this->rebateAgslotTableCount = 0; //AG桌上遊戲返點次數 opcode 1331
        $this->rebateAgslotJackpotAmount = 0; //AG累積彩池返點金額 opcode 1332
        $this->rebateAgslotJackpotCount = 0; //AG累積彩池返點次數 opcode 1332
        $this->rebateAgslotFishingAmount = 0; //AG捕魚機返點金額 opcode 1333
        $this->rebateAgslotFishingCount = 0; //AG捕魚機返點次數 opcode 1333
        $this->rebateAgslotPokerAmount = 0; //AG視頻撲克返點金額 opcode 1334
        $this->rebateAgslotPokerCount = 0; //AG視頻撲克返點次數 opcode 1334
        $this->rebateMWSlotsAmount = 0; //MW老虎機返點金額 opcode 1343
        $this->rebateMWSlotsCount = 0; //MW老虎機返點次數 opcode 1343
        $this->rebateMWTableAmount = 0; //MW桌上遊戲返點金額 opcode 1344
        $this->rebateMWTableCount = 0; //MW桌上遊戲返點次數 opcode 1344
        $this->rebateMWArcadeAmount = 0; //MW大型機台返點金額 opcode 1345
        $this->rebateMWArcadeCount = 0; //MW大型機台返點次數 opcode 1345
        $this->rebateMWFishingAmount = 0; //MW捕魚機返點金額 opcode 1346
        $this->rebateMWFishingCount = 0; //MW捕魚機返點次數 opcode 1346
        $this->rebateINSportAmount = 0; //IN體育返點金額 opcode 1352
        $this->rebateINSportCount = 0; //IN體育返點次數 opcode 1352
        $this->rebateRTSlotsAmount = 0; //RT老虎機返點金額 opcode 1379
        $this->rebateRTSlotsCount = 0; //RT老虎機返點次數 opcode 1379
        $this->rebateRTTableAmount = 0; //RT桌上遊戲返點金額 opcode 1380
        $this->rebateRTTableCount = 0; //RT桌上遊戲返點次數 opcode 1380
        $this->rebateSGSlotsAmount = 0; //SG老虎機返點金額 opcode 1383
        $this->rebateSGSlotsCount = 0; //SG老虎機返點次數 opcode 1383
        $this->rebateSGTableAmount = 0; //SG桌上遊戲返點金額 opcode 1384
        $this->rebateSGTableCount = 0; //SG桌上遊戲返點次數 opcode 1384
        $this->rebateSGJackpotAmount = 0; //SG累積彩池返點金額 opcode 1385
        $this->rebateSGJackpotCount = 0; //SG累積彩池返點次數 opcode 1385
        $this->rebateSGArcadeAmount = 0; //SG大型機台返點金額 opcode 1386
        $this->rebateSGArcadeCount = 0; //SG大型機台返點次數 opcode 1386
        $this->rebateVRVrAmount = 0; //VR真人彩返點金額 opcode 1391
        $this->rebateVRVrCount = 0; //VR真人彩返點次數 opcode 1391
        $this->rebateVRLottoAmount = 0; //VR國家彩返點金額 opcode 1392
        $this->rebateVRLottoCount = 0; //VR國家彩返點次數 opcode 1392
        $this->rebateVRMarksixAmount = 0; //VR六合彩返點金額 opcode 1393
        $this->rebateVRMarksixCount = 0; //VR六合彩返點次數 opcode 1393
        $this->rebatePT2SlotsAmount = 0; //ptⅡ真人彩返點金額 opcode 1421
        $this->rebatePT2SlotsCount = 0; //ptⅡ真人彩返點次數 opcode 1421
        $this->rebatePT2JackpotAmount = 0; //ptⅡ國家彩返點金額 opcode 1422
        $this->rebatePT2JackpotCount = 0; //ptⅡ國家彩返點次數 opcode 1422
        $this->rebatePT2FishingAmount = 0; //ptⅡ六合彩返點金額 opcode 1423
        $this->rebatePT2FishingCount = 0; //ptⅡ六合彩返點次數 opcode 1423
        $this->rebatePT2TableAmount = 0; //ptⅡ桌上遊戲返點金額 opcode 1427
        $this->rebatePT2TableCount = 0; //ptⅡ桌上遊戲返點次數 opcode 1427
        $this->rebatePT2FeatureAmount = 0; //ptⅡ特色遊戲返點金額 opcode 1428
        $this->rebatePT2FeatureCount = 0; //ptⅡ特色遊戲返點次數 opcode 1428
        $this->rebateBngSlotsAmount = 0; //BNG老虎機返點金額 opcode 1431
        $this->rebateBngSlotsCount = 0; //BNG老虎機返點次數 opcode 1431
        $this->rebateEVOAmount = 0; //EVO視訊返點金額 opcode 1434
        $this->rebateEVOCount = 0; //EVO視訊返點次數 opcode 1434
        $this->rebateGNSJackpotAmount = 0;//GNS累積彩池返點金額 opcode 1436
        $this->rebateGNSJackpotCount = 0;//GNS累積彩池返點次數 opcode 1436
        $this->rebateGNSFeatureAmount = 0; //GNS特色遊戲返點金額 opcode 1446
        $this->rebateGNSFeatureCount = 0; //GNS特色遊戲返點次數 opcode 1446
        $this->rebateKYAmount = 0; //開元棋牌返點金額 opcode 1448
        $this->rebateKYCount = 0; //開元棋牌返點次數 opcode 1448
        $this->rebateGNSTableGamesAmount = 0; //GNS桌上遊戲返點金額 opcode 1450
        $this->rebateGNSTableGamesCount = 0; //GNS桌上遊戲返點次數 opcode 1450
        $this->rebateAmount = 0; //返點總金額
        $this->rebateCount = 0; //返點總次數
    }

    /**
     * 回傳編號
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定編號
     * 僅用在測試
     *
     * @param integer $id
     * @return StatCashRebate
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * 取得統計日期
     *
     * @return \DateTime
     */
    public function getAt()
    {
        return $this->at;
    }

    /**
     * 取得使用者編號
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 設定幣別
     *
     * @param integer $currency
     * @return StatCashRebate
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * 回傳幣別
     *
     * @return integer
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * 設定廳主
     *
     * @param integer $domain
     * @return StatCashRebate
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * 回傳廳主
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 設定上層ID
     *
     * @param integer $parentId
     * @return StatCashRebate
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * 回傳上層ID
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * 設定 getXXX(), setXXX(), addXXX() 等操作欄位的方法。若方法已存在則呼叫原方法
     *
     * @param string $methodName 方法名稱
     * @param array  $args       參數陣列
     * @return mixed
     */
    public function __call($methodName, $args = [])
    {
        // 已存在的方法直接執行
        if (method_exists($this, $methodName)) {
            return call_user_method($methodName, $this, $args);
        }

        $value = 1;

        if (isset($args[0])) {
            $value = $args[0];
        }

        $methodType = substr($methodName, 0, 3);
        $variableName = lcfirst(substr($methodName, 3));

        if ($methodType == 'get') {
            return $this->$variableName;
        }

        if ($methodType == 'set') {
            $this->$variableName = $value;

            return $this;
        }

        if ($methodType == 'add') {
            $this->$variableName += $value;

            return $this;
        }
    }
}
