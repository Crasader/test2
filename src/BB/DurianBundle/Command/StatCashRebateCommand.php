<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\StatOpcode;

/**
 * 統計現金返點金額、次數
 *
 * @author Sweet 2014.11.18
 */
class StatCashRebateCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * 程式開始執行時間
     *
     * @var \DateTime
     */
    private $startTime;

    /**
     * 每次下語法刪掉的筆數
     *
     * @var integer
     */
    private $batchSize;

    /**
     * 等待時間
     *
     * @var integer
     */
    private $waitTime;

    /**
     * 增加時間
     *
     * @var integer
     */
    private $addTime;

    /**
     * 若設為true, 則會分批刪除資料
     *
     * @var bool
     */
    private $slowly = false;

    /**
     * 若設為true, 則不更改背景最後執行成功時間，避免自動觸發時統計區間錯誤
     *
     * @var bool
     */
    private $recover = false;

    /**
     * 是否為測試環境
     *
     * @var boolean
     */
    private $isTest;

    /**
     * 返點統計資料
     *
     * @var array
     */
    private $rebates = [];

    /**
     * 返點對應表
     *
     * @var array
     */
    private $rebateOpcodeMap = [
        '1024' => 'rebate_ball',
        '1025' => 'rebate_keno',
        '1026' => 'rebate_video',
        '1027' => 'rebate_sport',
        '1028' => 'rebate_prob',
        '1048' => 'rebate_lottery',
        '1050' => 'rebate_bbplay',
        '1054' => 'rebate_offer',
        '1055' => 'rebate_bbvideo',
        '1057' => 'rebate_ttvideo',
        '1059' => 'rebate_armvideo',
        '1061' => 'rebate_xpjvideo',
        '1063' => 'rebate_yfvideo',
        '1065' => 'rebate_3d',
        '1067' => 'rebate_battle',
        '1069' => 'rebate_virtual',
        '1071' => 'rebate_vipvideo',
        '1082' => 'rebate_agvideo',
        '1091' => 'rebate_pt',
        '1093' => 'rebate_lt',
        '1096' => 'rebate_mi',
        '1108' => 'rebate_ab',
        '1116' => 'rebate_mg',
        '1124' => 'rebate_og',
        '1135' => 'rebate_sb',
        '1143' => 'rebate_gd',
        '1155' => 'rebate_sa',
        '1165' => 'rebate_gns',
        '1168' => 'rebate_mg_jackpot',
        '1169' => 'rebate_mg_slots',
        '1170' => 'rebate_mg_feature',
        '1171' => 'rebate_mg_table',
        '1172' => 'rebate_mg_mobile',
        '1185' => 'rebate_isb',
        '1189' => 'rebate_bbfg',
        '1191' => 'rebate_bc',
        '1193' => 'rebate_bb_slots',
        '1194' => 'rebate_bb_table',
        '1195' => 'rebate_bb_arcade',
        '1196' => 'rebate_bb_scratch',
        '1197' => 'rebate_bb_feature',
        '1203' => 'rebate_bb_treasure',
        '1205' => 'rebate_isb_slots',
        '1206' => 'rebate_isb_table',
        '1207' => 'rebate_isb_jackpot',
        '1208' => 'rebate_isb_poker',
        '1220' => 'rebate_888_fishing',
        '1224' => 'rebate_pt_slots',
        '1225' => 'rebate_pt_table',
        '1226' => 'rebate_pt_jackpot',
        '1227' => 'rebate_pt_arcade',
        '1228' => 'rebate_pt_scratch',
        '1229' => 'rebate_pt_poker',
        '1236' => 'rebate_pt_unclassified',
        '1238' => 'rebate_gog',
        '1240' => 'rebate_sk_1',
        '1241' => 'rebate_sk_2',
        '1242' => 'rebate_sk_3',
        '1243' => 'rebate_sk_4',
        '1244' => 'rebate_sk_5',
        '1245' => 'rebate_sk_6',
        '1260' => 'rebate_hb_slots',
        '1261' => 'rebate_hb_table',
        '1262' => 'rebate_hb_poker',
        '1274' => 'rebate_bg_live',
        '1284' => 'rebate_fishing_master',
        '1286' => 'rebate_pp_slots',
        '1287' => 'rebate_pp_table',
        '1288' => 'rebate_pp_jackpot',
        '1289' => 'rebate_pp_feature',
        '1318' => 'rebate_pt_fishing',
        '1320' => 'rebate_gns_slots',
        '1322' => 'rebate_gns_fishing',
        '1324' => 'rebate_jdb_slots',
        '1325' => 'rebate_jdb_arcade',
        '1326' => 'rebate_jdb_fishing',
        '1330' => 'rebate_agslot_slots',
        '1331' => 'rebate_agslot_table',
        '1332' => 'rebate_agslot_jackpot',
        '1333' => 'rebate_agslot_fishing',
        '1334' => 'rebate_agslot_poker',
        '1343' => 'rebate_mw_slots',
        '1344' => 'rebate_mw_table',
        '1345' => 'rebate_mw_arcade',
        '1346' => 'rebate_mw_fishing',
        '1352' => 'rebate_in_sport',
        '1379' => 'rebate_rt_slots',
        '1380' => 'rebate_rt_table',
        '1383' => 'rebate_sg_slots',
        '1384' => 'rebate_sg_table',
        '1385' => 'rebate_sg_jackpot',
        '1386' => 'rebate_sg_arcade',
        '1391' => 'rebate_vr_vr',
        '1392' => 'rebate_vr_lotto',
        '1393' => 'rebate_vr_marksix',
        '1421' => 'rebate_pt2_slots',
        '1422' => 'rebate_pt2_jackpot',
        '1423' => 'rebate_pt2_fishing',
        '1427' => 'rebate_pt2_table',
        '1428' => 'rebate_pt2_feature',
        '1431' => 'rebate_bng_slots',
        '1434' => 'rebate_evo',
        '1436' => 'rebate_gns_jackpot',
        '1446' => 'rebate_gns_feature',
        '1448' => 'rebate_ky',
        '1450' => 'rebate_gns_table'
    ];

    /**
     * 返點沖銷對應表
     *
     * @var array
     */
    private $negativeOpcodeMap = [
        '1029' => 'rebate_ball',
        '1030' => 'rebate_keno',
        '1031' => 'rebate_video',
        '1032' => 'rebate_sport',
        '1033' => 'rebate_prob',
        '1049' => 'rebate_lottery',
        '1051' => 'rebate_bbplay',
        '1056' => 'rebate_bbvideo',
        '1058' => 'rebate_ttvideo',
        '1060' => 'rebate_armvideo',
        '1062' => 'rebate_xpjvideo',
        '1064' => 'rebate_yfvideo',
        '1066' => 'rebate_3d',
        '1068' => 'rebate_battle',
        '1070' => 'rebate_virtual',
        '1072' => 'rebate_vipvideo',
        '1083' => 'rebate_agvideo',
        '1092' => 'rebate_pt',
        '1094' => 'rebate_lt',
        '1097' => 'rebate_mi',
        '1109' => 'rebate_ab',
        '1117' => 'rebate_mg',
        '1125' => 'rebate_og',
        '1136' => 'rebate_sb',
        '1144' => 'rebate_gd',
        '1156' => 'rebate_sa',
        '1166' => 'rebate_gns',
        '1173' => 'rebate_mg_jackpot',
        '1174' => 'rebate_mg_slots',
        '1175' => 'rebate_mg_feature',
        '1176' => 'rebate_mg_table',
        '1177' => 'rebate_mg_mobile',
        '1186' => 'rebate_isb',
        '1190' => 'rebate_bbfg',
        '1192' => 'rebate_bc',
        '1198' => 'rebate_bb_slots',
        '1199' => 'rebate_bb_table',
        '1200' => 'rebate_bb_arcade',
        '1201' => 'rebate_bb_scratch',
        '1202' => 'rebate_bb_feature',
        '1204' => 'rebate_bb_treasure',
        '1209' => 'rebate_isb_slots',
        '1210' => 'rebate_isb_table',
        '1211' => 'rebate_isb_jackpot',
        '1212' => 'rebate_isb_poker',
        '1221' => 'rebate_888_fishing',
        '1230' => 'rebate_pt_slots',
        '1231' => 'rebate_pt_table',
        '1232' => 'rebate_pt_jackpot',
        '1233' => 'rebate_pt_arcade',
        '1234' => 'rebate_pt_scratch',
        '1235' => 'rebate_pt_poker',
        '1237' => 'rebate_pt_unclassified',
        '1239' => 'rebate_gog',
        '1246' => 'rebate_sk_1',
        '1247' => 'rebate_sk_2',
        '1248' => 'rebate_sk_3',
        '1249' => 'rebate_sk_4',
        '1250' => 'rebate_sk_5',
        '1251' => 'rebate_sk_6',
        '1263' => 'rebate_hb_slots',
        '1264' => 'rebate_hb_table',
        '1265' => 'rebate_hb_poker',
        '1275' => 'rebate_bg_live',
        '1285' => 'rebate_fishing_master',
        '1290' => 'rebate_pp_slots',
        '1291' => 'rebate_pp_table',
        '1292' => 'rebate_pp_jackpot',
        '1293' => 'rebate_pp_feature',
        '1319' => 'rebate_pt_fishing',
        '1321' => 'rebate_gns_slots',
        '1323' => 'rebate_gns_fishing',
        '1327' => 'rebate_jdb_slots',
        '1328' => 'rebate_jdb_arcade',
        '1329' => 'rebate_jdb_fishing',
        '1335' => 'rebate_agslot_slots',
        '1336' => 'rebate_agslot_table',
        '1337' => 'rebate_agslot_jackpot',
        '1338' => 'rebate_agslot_fishing',
        '1339' => 'rebate_agslot_poker',
        '1347' => 'rebate_mw_slots',
        '1348' => 'rebate_mw_table',
        '1349' => 'rebate_mw_arcade',
        '1350' => 'rebate_mw_fishing',
        '1353' => 'rebate_in_sport',
        '1381' => 'rebate_rt_slots',
        '1382' => 'rebate_rt_table',
        '1387' => 'rebate_sg_slots',
        '1388' => 'rebate_sg_table',
        '1389' => 'rebate_sg_jackpot',
        '1390' => 'rebate_sg_arcade',
        '1394' => 'rebate_vr_vr',
        '1395' => 'rebate_vr_lotto',
        '1396' => 'rebate_vr_marksix',
        '1424' => 'rebate_pt2_slots',
        '1425' => 'rebate_pt2_jackpot',
        '1426' => 'rebate_pt2_fishing',
        '1429' => 'rebate_pt2_table',
        '1430' => 'rebate_pt2_feature',
        '1432' => 'rebate_bng_slots',
        '1435' => 'rebate_evo',
        '1437' => 'rebate_gns_jackpot',
        '1447' => 'rebate_gns_feature',
        '1449' => 'rebate_ky',
        '1451' => 'rebate_gns_table'
    ];

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:stat-cash-rebate')
            ->setDescription('統計現金返點金額、次數')
            ->addOption('start-date', null, InputOption::VALUE_REQUIRED, '統計日期起', null)
            ->addOption('end-date', null, InputOption::VALUE_REQUIRED, '統計日期迄', null)
            ->addOption('batch-size', null, InputOption::VALUE_OPTIONAL, '批次處理的數量', null)
            ->addOption('wait-sec', null, InputOption::VALUE_OPTIONAL, '等待秒數', null)
            ->addOption('slow', null, InputOption::VALUE_NONE, '分批刪除資料, 以防卡語法')
            ->addOption('recover', null, InputOption::VALUE_NONE, '補跑統計資料，不更改背景最後執行成功時間，避免自動觸發時統計區間錯誤')
            ->setHelp(<<<EOT
統計現金返點金額、次數
app/console durian:stat-cash-rebate --start-date="2013/01/01" --end-date="2013/01/31"

補跑統計現金返點金額、次數，不更改背景最後執行成功時間，避免自動觸發時統計區間錯誤
app/console durian:stat-cash-rebate --start-date="2013/01/01" --end-date="2013/01/01" --recover
EOT
             );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setOptions($input);

        // 手動補跑時，不修改 background_process 資料，避免影響下次背景執行區間
        if (!$this->recover) {
            $bgMonitor = $this->getContainer()->get('durian.monitor.background');
            $bgMonitor->commandStart('stat-cash-rebate');
        }

        $this->output = $output;
        $this->start();

        $startDate = new \DateTime($input->getOption('start-date'));
        $startDate->setTime(12, 0, 0);
        $endDate = new \DateTime($input->getOption('end-date'));
        $endDate->setTime(12, 0, 0);

        //刪除原本資料
        $this->removeData($startDate, $endDate);

        $msgNum = 0;
        while ($startDate <= $endDate) {
            $msgNum += $this->sumStat($startDate);
            $startDate->add($this->addTime);
            usleep($this->waitTime);
        }

        $this->end();

        // 手動補跑時，不修改 background_process 資料，避免影響下次背景執行區間
        if (!$this->recover) {
            $bgMonitor->setMsgNum($msgNum);
            $bgMonitor->setLastEndTime($endDate);
            $bgMonitor->commandEnd();
        }
    }

    /**
     * 開始執行、紀錄開始時間
     */
    private function start()
    {
        $this->startTime = new \DateTime;
    }

    /**
     * 程式結束顯示處理時間、記憶體
     */
    private function end()
    {
        $endTime = new \DateTime;
        $costTime = $endTime->diff($this->startTime, true);
        $this->output->writeln('Execute time: ' . $costTime->format('%H:%I:%S'));

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);
        $this->output->writeln("Memory MAX use: $usage M");
    }

    /**
     * 設定參數
     *
     * @param InputInterface $input
     */
    private function setOptions(InputInterface $input)
    {
        $this->slowly = false;

        if ($input->getOption('slow')) {
            $this->slowly = true;
        }

        if ($input->getOption('recover')) {
            $this->recover = true;
        }

        $this->batchSize = 500;

        if ($input->getOption('batch-size')) {
            $this->batchSize = $input->getOption('batch-size');
        }

        $this->waitTime = 500000;

        if ($input->getOption('wait-sec')) {
            $this->waitTime = $input->getOption('wait-sec') * 1000000;
        }

        $this->addTime = new \DateInterval('P1D');

        $this->isTest = false;

        if ($this->getContainer()->getParameter('kernel.environment') == 'test') {
            $this->isTest = true;
        }
    }

    /**
     * 彙總統計資料到會員統計返點資料表
     *
     * @param \DateTime $startDate
     * @return integer $num
     */
    private function convert(\DateTime $startDate)
    {
        $conn = $this->getEntityManager('his')->getConnection();
        $conn->connect('slave');

        $at = $startDate->format('Y-m-d H:i:s');

        $executeCount = 0;
        $num = 0;
        $inserts = [];

        foreach ($this->rebates as $userId => $rebate) {
            if (++$executeCount % $this->batchSize == 0) {
                $num += $this->doMultiInsert($inserts);
                $inserts = [];
            }

            $inserts[] = [
                0,
                $at,
                $userId,
                $rebate['currency'],
                $rebate['domain'],
                $rebate['parent_id'],
                $rebate['rebate_ball_amount'],
                $rebate['rebate_ball_count'],
                $rebate['rebate_keno_amount'],
                $rebate['rebate_keno_count'],
                $rebate['rebate_video_amount'],
                $rebate['rebate_video_count'],
                $rebate['rebate_sport_amount'],
                $rebate['rebate_sport_count'],
                $rebate['rebate_prob_amount'],
                $rebate['rebate_prob_count'],
                $rebate['rebate_lottery_amount'],
                $rebate['rebate_lottery_count'],
                $rebate['rebate_bbplay_amount'],
                $rebate['rebate_bbplay_count'],
                $rebate['rebate_offer_amount'],
                $rebate['rebate_offer_count'],
                $rebate['rebate_bbvideo_amount'],
                $rebate['rebate_bbvideo_count'],
                $rebate['rebate_ttvideo_amount'],
                $rebate['rebate_ttvideo_count'],
                $rebate['rebate_armvideo_amount'],
                $rebate['rebate_armvideo_count'],
                $rebate['rebate_xpjvideo_amount'],
                $rebate['rebate_xpjvideo_count'],
                $rebate['rebate_yfvideo_amount'],
                $rebate['rebate_yfvideo_count'],
                $rebate['rebate_3d_amount'],
                $rebate['rebate_3d_count'],
                $rebate['rebate_battle_amount'],
                $rebate['rebate_battle_count'],
                $rebate['rebate_virtual_amount'],
                $rebate['rebate_virtual_count'],
                $rebate['rebate_vipvideo_amount'],
                $rebate['rebate_vipvideo_count'],
                $rebate['rebate_agvideo_amount'],
                $rebate['rebate_agvideo_count'],
                $rebate['rebate_amount'],
                $rebate['rebate_count'],
                $rebate['rebate_pt_amount'],
                $rebate['rebate_pt_count'],
                $rebate['rebate_lt_amount'],
                $rebate['rebate_lt_count'],
                $rebate['rebate_mi_amount'],
                $rebate['rebate_mi_count'],
                $rebate['rebate_ab_amount'],
                $rebate['rebate_ab_count'],
                $rebate['rebate_mg_amount'],
                $rebate['rebate_mg_count'],
                $rebate['rebate_og_amount'],
                $rebate['rebate_og_count'],
                $rebate['rebate_sb_amount'],
                $rebate['rebate_sb_count'],
                $rebate['rebate_gd_amount'],
                $rebate['rebate_gd_count'],
                $rebate['rebate_sa_amount'],
                $rebate['rebate_sa_count'],
                $rebate['rebate_gns_amount'],
                $rebate['rebate_gns_count'],
                $rebate['rebate_mg_jackpot_amount'],
                $rebate['rebate_mg_jackpot_count'],
                $rebate['rebate_mg_slots_amount'],
                $rebate['rebate_mg_slots_count'],
                $rebate['rebate_mg_feature_amount'],
                $rebate['rebate_mg_feature_count'],
                $rebate['rebate_mg_table_amount'],
                $rebate['rebate_mg_table_count'],
                $rebate['rebate_mg_mobile_amount'],
                $rebate['rebate_mg_mobile_count'],
                $rebate['rebate_isb_amount'],
                $rebate['rebate_isb_count'],
                $rebate['rebate_bbfg_amount'],
                $rebate['rebate_bbfg_count'],
                $rebate['rebate_bc_amount'],
                $rebate['rebate_bc_count'],
                $rebate['rebate_bb_slots_amount'],
                $rebate['rebate_bb_slots_count'],
                $rebate['rebate_bb_table_amount'],
                $rebate['rebate_bb_table_count'],
                $rebate['rebate_bb_arcade_amount'],
                $rebate['rebate_bb_arcade_count'],
                $rebate['rebate_bb_scratch_amount'],
                $rebate['rebate_bb_scratch_count'],
                $rebate['rebate_bb_feature_amount'],
                $rebate['rebate_bb_feature_count'],
                $rebate['rebate_bb_treasure_amount'],
                $rebate['rebate_bb_treasure_count'],
                $rebate['rebate_isb_slots_amount'],
                $rebate['rebate_isb_slots_count'],
                $rebate['rebate_isb_table_amount'],
                $rebate['rebate_isb_table_count'],
                $rebate['rebate_isb_jackpot_amount'],
                $rebate['rebate_isb_jackpot_count'],
                $rebate['rebate_isb_poker_amount'],
                $rebate['rebate_isb_poker_count'],
                $rebate['rebate_888_fishing_amount'],
                $rebate['rebate_888_fishing_count'],
                $rebate['rebate_pt_slots_amount'],
                $rebate['rebate_pt_slots_count'],
                $rebate['rebate_pt_table_amount'],
                $rebate['rebate_pt_table_count'],
                $rebate['rebate_pt_jackpot_amount'],
                $rebate['rebate_pt_jackpot_count'],
                $rebate['rebate_pt_arcade_amount'],
                $rebate['rebate_pt_arcade_count'],
                $rebate['rebate_pt_scratch_amount'],
                $rebate['rebate_pt_scratch_count'],
                $rebate['rebate_pt_poker_amount'],
                $rebate['rebate_pt_poker_count'],
                $rebate['rebate_pt_unclassified_amount'],
                $rebate['rebate_pt_unclassified_count'],
                $rebate['rebate_gog_amount'],
                $rebate['rebate_gog_count'],
                $rebate['rebate_sk_1_amount'],
                $rebate['rebate_sk_1_count'],
                $rebate['rebate_sk_2_amount'],
                $rebate['rebate_sk_2_count'],
                $rebate['rebate_sk_3_amount'],
                $rebate['rebate_sk_3_count'],
                $rebate['rebate_sk_4_amount'],
                $rebate['rebate_sk_4_count'],
                $rebate['rebate_sk_5_amount'],
                $rebate['rebate_sk_5_count'],
                $rebate['rebate_sk_6_amount'],
                $rebate['rebate_sk_6_count'],
                $rebate['rebate_hb_slots_amount'],
                $rebate['rebate_hb_slots_count'],
                $rebate['rebate_hb_table_amount'],
                $rebate['rebate_hb_table_count'],
                $rebate['rebate_hb_poker_amount'],
                $rebate['rebate_hb_poker_count'],
                $rebate['rebate_bg_live_amount'],
                $rebate['rebate_bg_live_count'],
                $rebate['rebate_fishing_master_amount'],
                $rebate['rebate_fishing_master_count'],
                $rebate['rebate_pp_slots_amount'],
                $rebate['rebate_pp_slots_count'],
                $rebate['rebate_pp_table_amount'],
                $rebate['rebate_pp_table_count'],
                $rebate['rebate_pp_jackpot_amount'],
                $rebate['rebate_pp_jackpot_count'],
                $rebate['rebate_pp_feature_amount'],
                $rebate['rebate_pp_feature_count'],
                $rebate['rebate_pt_fishing_amount'],
                $rebate['rebate_pt_fishing_count'],
                $rebate['rebate_gns_slots_amount'],
                $rebate['rebate_gns_slots_count'],
                $rebate['rebate_gns_fishing_amount'],
                $rebate['rebate_gns_fishing_count'],
                $rebate['rebate_jdb_slots_amount'],
                $rebate['rebate_jdb_slots_count'],
                $rebate['rebate_jdb_arcade_amount'],
                $rebate['rebate_jdb_arcade_count'],
                $rebate['rebate_jdb_fishing_amount'],
                $rebate['rebate_jdb_fishing_count'],
                $rebate['rebate_agslot_slots_amount'],
                $rebate['rebate_agslot_slots_count'],
                $rebate['rebate_agslot_table_amount'],
                $rebate['rebate_agslot_table_count'],
                $rebate['rebate_agslot_jackpot_amount'],
                $rebate['rebate_agslot_jackpot_count'],
                $rebate['rebate_agslot_fishing_amount'],
                $rebate['rebate_agslot_fishing_count'],
                $rebate['rebate_agslot_poker_amount'],
                $rebate['rebate_agslot_poker_count'],
                $rebate['rebate_mw_slots_amount'],
                $rebate['rebate_mw_slots_count'],
                $rebate['rebate_mw_table_amount'],
                $rebate['rebate_mw_table_count'],
                $rebate['rebate_mw_arcade_amount'],
                $rebate['rebate_mw_arcade_count'],
                $rebate['rebate_mw_fishing_amount'],
                $rebate['rebate_mw_fishing_count'],
                $rebate['rebate_in_sport_amount'],
                $rebate['rebate_in_sport_count'],
                $rebate['rebate_rt_slots_amount'],
                $rebate['rebate_rt_slots_count'],
                $rebate['rebate_rt_table_amount'],
                $rebate['rebate_rt_table_count'],
                $rebate['rebate_sg_slots_amount'],
                $rebate['rebate_sg_slots_count'],
                $rebate['rebate_sg_table_amount'],
                $rebate['rebate_sg_table_count'],
                $rebate['rebate_sg_jackpot_amount'],
                $rebate['rebate_sg_jackpot_count'],
                $rebate['rebate_sg_arcade_amount'],
                $rebate['rebate_sg_arcade_count'],
                $rebate['rebate_vr_vr_amount'],
                $rebate['rebate_vr_vr_count'],
                $rebate['rebate_vr_lotto_amount'],
                $rebate['rebate_vr_lotto_count'],
                $rebate['rebate_vr_marksix_amount'],
                $rebate['rebate_vr_marksix_count'],
                $rebate['rebate_pt2_slots_amount'],
                $rebate['rebate_pt2_slots_count'],
                $rebate['rebate_pt2_jackpot_amount'],
                $rebate['rebate_pt2_jackpot_count'],
                $rebate['rebate_pt2_fishing_amount'],
                $rebate['rebate_pt2_fishing_count'],
                $rebate['rebate_pt2_table_amount'],
                $rebate['rebate_pt2_table_count'],
                $rebate['rebate_pt2_feature_amount'],
                $rebate['rebate_pt2_feature_count'],
                $rebate['rebate_bng_slots_amount'],
                $rebate['rebate_bng_slots_count'],
                $rebate['rebate_evo_amount'],
                $rebate['rebate_evo_count'],
                $rebate['rebate_gns_jackpot_amount'],
                $rebate['rebate_gns_jackpot_count'],
                $rebate['rebate_gns_feature_amount'],
                $rebate['rebate_gns_feature_count'],
                $rebate['rebate_ky_amount'],
                $rebate['rebate_ky_count'],
                $rebate['rebate_gns_table_amount'],
                $rebate['rebate_gns_table_count']
            ];
        }

        if ($inserts) {
            $num += $this->doMultiInsert($inserts);
        }

        return $num;
    }

    /**
     * 批次新增
     *
     * @param array $inserts 要新增的資料
     * @return integer
     */
    private function doMultiInsert(Array $inserts)
    {
        if (!$inserts) {
            return 0;
        }

        $conn = $this->getEntityManager('his')->getConnection();

        $values = [];
        foreach ($inserts as $insert) {
            $values[] = sprintf("('%s')", implode("','", $insert));
        }

        $sql = 'INSERT INTO stat_cash_rebate (id,at,user_id,currency,domain,parent_id,' .
            'rebate_ball_amount,rebate_ball_count,rebate_keno_amount,rebate_keno_count,' .
            'rebate_video_amount,rebate_video_count,rebate_sport_amount,rebate_sport_count,' .
            'rebate_prob_amount,rebate_prob_count,rebate_lottery_amount,rebate_lottery_count,' .
            'rebate_bbplay_amount,rebate_bbplay_count,rebate_offer_amount,rebate_offer_count,' .
            'rebate_bbvideo_amount,rebate_bbvideo_count,rebate_ttvideo_amount,rebate_ttvideo_count,' .
            'rebate_armvideo_amount,rebate_armvideo_count,rebate_xpjvideo_amount,rebate_xpjvideo_count,' .
            'rebate_yfvideo_amount,rebate_yfvideo_count,rebate_3d_amount,rebate_3d_count,' .
            'rebate_battle_amount,rebate_battle_count,rebate_virtual_amount,rebate_virtual_count,' .
            'rebate_vipvideo_amount,rebate_vipvideo_count,rebate_agvideo_amount,rebate_agvideo_count,' .
            'rebate_amount,rebate_count,rebate_pt_amount,rebate_pt_count,rebate_lt_amount,rebate_lt_count,' .
            'rebate_mi_amount,rebate_mi_count,rebate_ab_amount,rebate_ab_count,rebate_mg_amount,rebate_mg_count,' .
            'rebate_og_amount,rebate_og_count,rebate_sb_amount,rebate_sb_count,' .
            'rebate_gd_amount,rebate_gd_count,rebate_sa_amount,rebate_sa_count,rebate_gns_amount,rebate_gns_count,' .
            'rebate_mg_jackpot_amount,rebate_mg_jackpot_count,rebate_mg_slots_amount,rebate_mg_slots_count,' .
            'rebate_mg_feature_amount,rebate_mg_feature_count,rebate_mg_table_amount,rebate_mg_table_count,' .
            'rebate_mg_mobile_amount,rebate_mg_mobile_count,rebate_isb_amount,rebate_isb_count,' .
            'rebate_bbfg_amount,rebate_bbfg_count,rebate_bc_amount,rebate_bc_count,' .
            'rebate_bb_slots_amount,rebate_bb_slots_count,' .
            'rebate_bb_table_amount,rebate_bb_table_count,rebate_bb_arcade_amount,rebate_bb_arcade_count,' .
            'rebate_bb_scratch_amount,rebate_bb_scratch_count,rebate_bb_feature_amount,' .
            'rebate_bb_feature_count,rebate_bb_treasure_amount,rebate_bb_treasure_count,' .
            'rebate_isb_slots_amount,rebate_isb_slots_count,rebate_isb_table_amount,' .
            'rebate_isb_table_count,rebate_isb_jackpot_amount,rebate_isb_jackpot_count,rebate_isb_poker_amount,' .
            'rebate_isb_poker_count,rebate_888_fishing_amount,rebate_888_fishing_count,' .
            'rebate_pt_slots_amount,rebate_pt_slots_count,rebate_pt_table_amount,' .
            'rebate_pt_table_count,rebate_pt_jackpot_amount,rebate_pt_jackpot_count,rebate_pt_arcade_amount,' .
            'rebate_pt_arcade_count,rebate_pt_scratch_amount,rebate_pt_scratch_count,rebate_pt_poker_amount,' .
            'rebate_pt_poker_count,rebate_pt_unclassified_amount,rebate_pt_unclassified_count,rebate_gog_amount,' .
            'rebate_gog_count,rebate_sk_1_amount,rebate_sk_1_count,rebate_sk_2_amount,rebate_sk_2_count,' .
            'rebate_sk_3_amount,rebate_sk_3_count,rebate_sk_4_amount,rebate_sk_4_count,rebate_sk_5_amount,' .
            'rebate_sk_5_count,rebate_sk_6_amount,rebate_sk_6_count,rebate_hb_slots_amount,rebate_hb_slots_count,' .
            'rebate_hb_table_amount,rebate_hb_table_count,rebate_hb_poker_amount,rebate_hb_poker_count,' .
            'rebate_bg_live_amount,rebate_bg_live_count,rebate_fishing_master_amount,' .
            'rebate_fishing_master_count,rebate_pp_slots_amount,rebate_pp_slots_count,' .
            'rebate_pp_table_amount,rebate_pp_table_count,rebate_pp_jackpot_amount,rebate_pp_jackpot_count,' .
            'rebate_pp_feature_amount,rebate_pp_feature_count,rebate_pt_fishing_amount,' .
            'rebate_pt_fishing_count,rebate_gns_slots_amount,rebate_gns_slots_count,rebate_gns_fishing_amount,' .
            'rebate_gns_fishing_count,rebate_jdb_slots_amount,rebate_jdb_slots_count,rebate_jdb_arcade_amount,' .
            'rebate_jdb_arcade_count,rebate_jdb_fishing_amount,rebate_jdb_fishing_count, ' .
            'rebate_agslot_slots_amount, rebate_agslot_slots_count, rebate_agslot_table_amount, ' .
            'rebate_agslot_table_count, rebate_agslot_jackpot_amount, rebate_agslot_jackpot_count, ' .
            'rebate_agslot_fishing_amount, rebate_agslot_fishing_count, rebate_agslot_poker_amount, ' .
            'rebate_agslot_poker_count, rebate_mw_slots_amount, rebate_mw_slots_count, ' .
            'rebate_mw_table_amount, rebate_mw_table_count, rebate_mw_arcade_amount, ' .
            'rebate_mw_arcade_count, rebate_mw_fishing_amount, rebate_mw_fishing_count, rebate_in_sport_amount, ' .
            'rebate_in_sport_count, rebate_rt_slots_amount, rebate_rt_slots_count, rebate_rt_table_amount, ' .
            'rebate_rt_table_count, rebate_sg_slots_amount, rebate_sg_slots_count, rebate_sg_table_amount, ' .
            'rebate_sg_table_count, rebate_sg_jackpot_amount, rebate_sg_jackpot_count, rebate_sg_arcade_amount, ' .
            'rebate_sg_arcade_count, rebate_vr_vr_amount, rebate_vr_vr_count, rebate_vr_lotto_amount, ' .
            'rebate_vr_lotto_count, rebate_vr_marksix_amount, rebate_vr_marksix_count, rebate_pt2_slots_amount, ' .
            'rebate_pt2_slots_count, rebate_pt2_jackpot_amount, ' .
            'rebate_pt2_jackpot_count, rebate_pt2_fishing_amount, rebate_pt2_fishing_count, rebate_pt2_table_amount, ' .
            'rebate_pt2_table_count, rebate_pt2_feature_amount, rebate_pt2_feature_count, ' .
            'rebate_bng_slots_amount, rebate_bng_slots_count, rebate_evo_amount, rebate_evo_count, ' .
            'rebate_gns_jackpot_amount, rebate_gns_jackpot_count, rebate_gns_feature_amount, rebate_gns_feature_count, ' .
            'rebate_ky_amount, rebate_ky_count, rebate_gns_table_amount, rebate_gns_table_count) VALUES ';

        // 測試環境要採用其他語法新增
        if ($this->isTest) {
            $multiSql = [];
            foreach ($values as $value) {
                $multiSql[] = $sql . $value;
            }

            $multiSql = implode(';', $multiSql);

            // sqlite的id是主鍵，insert不用指定id值
            $multiSql = str_replace('(id,', '(', $multiSql);
            $multiSql = str_replace("('0',", '(', $multiSql);

            return $conn->executeUpdate($multiSql);
        }

        $sql .= implode(',', $values);
        $ret = $conn->executeUpdate($sql);

        usleep($this->waitTime);

        return $ret;
    }

    /**
     * 刪除原本資料
     *
     * @param \DateTime $startDate 開始日期
     * @param \DateTime $endDate   結束日期
     */
    private function removeData(\DateTime $startDate, \DateTime $endDate)
    {
        $conn = $this->getEntityManager('his')->getConnection();

        $start = $startDate->format('Y-m-d H:i:s');
        $end = $endDate->format('Y-m-d H:i:s');
        $params = [
            $start,
            $end
        ];

        $sql = 'SELECT COUNT(id) FROM stat_cash_rebate WHERE at >= ? AND at <= ?';
        $count = $conn->fetchColumn($sql, $params);

        if ($count == 0) {
            return;
        }

        // 直接刪除
        if (!$this->slowly) {
            $sql = 'DELETE FROM stat_cash_rebate WHERE at >= ? AND at <= ?';
            $conn->executeUpdate($sql, $params);

            return;
        }

        // 慢慢刪除
        $sql = sprintf(
            'DELETE FROM stat_cash_rebate WHERE at >= ? AND at <= ? LIMIT %d',
            $this->batchSize
        );

        while ($count > 0) {
            $conn->executeUpdate($sql, $params);
            $count -= $this->batchSize;
            usleep($this->waitTime);
        }
    }

    /**
     * 從中介表統計返點金額、次數
     *
     * @param \DateTime $statDate 統計日期
     * @return integer $num
     */
    private function sumStat(\DateTime $statDate)
    {
        $at = $statDate->format('Y-m-d H:i:s');

        $conn = $this->getEntityManager('his')->getConnection();
        $conn->connect('slave');

        $countSql = 'SELECT count(1) FROM stat_cash_opcode WHERE at = ? AND opcode IN (?)';
        $sql = 'SELECT * FROM stat_cash_opcode WHERE at = ? AND opcode IN (?) ORDER BY user_id, opcode LIMIT ?, ?';

        $params = [
            $at,
            StatOpcode::$cashRebateOpcode
        ];

        $types = [
            \PDO::PARAM_STR,
            \Doctrine\DBAL\Connection::PARAM_INT_ARRAY
        ];

        $offset = 0;
        $limit = 50000;
        $total = $conn->executeQuery($countSql, $params, $types)->fetchColumn();

        $this->rebates = [];
        $userCount = 0;
        $lastUserId = 0;
        $num = 0;

        while ($offset < $total) {
            $params = [
                $at,
                StatOpcode::$cashRebateOpcode,
                $offset,
                $limit
            ];

            $types = [
                \PDO::PARAM_STR,
                \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
                \PDO::PARAM_INT,
                \PDO::PARAM_INT
            ];

            $stats = $conn->executeQuery($sql, $params, $types);
            $offset += $limit;

            while ($stat = $stats->fetch()) {
                $userId = $stat['user_id'];
                if ($userId != $lastUserId) {
                    $userCount++;
                }

                if ($userCount % 1000 == 0 && $userId != $lastUserId) {
                    $num += $this->convert($statDate);
                    $this->rebates = [];
                }

                $opcode = $stat['opcode'];

                if (!isset($this->rebates[$userId])) {
                    $this->init($stat);
                }

                if (in_array($opcode, StatOpcode::$negativeOpcode)) {
                    $column = $this->negativeOpcodeMap[$opcode];
                    $this->rebates[$userId][$column . '_amount'] += $stat['amount'];
                    $this->rebates[$userId][$column . '_count'] -= $stat['count'];
                    $this->rebates[$userId]['rebate_amount'] += $stat['amount'];
                    $this->rebates[$userId]['rebate_count'] -= $stat['count'];
                } else {
                    $column = $this->rebateOpcodeMap[$opcode];
                    $this->rebates[$userId][$column . '_amount'] += $stat['amount'];
                    $this->rebates[$userId][$column . '_count'] += $stat['count'];
                    $this->rebates[$userId]['rebate_amount'] += $stat['amount'];
                    $this->rebates[$userId]['rebate_count'] += $stat['count'];
                }

                $lastUserId = $stat['user_id'];
            }
        }

        if ($this->rebates) {
            $num += $this->convert($statDate);
            $this->rebates = [];
        }

        return $num;
    }

    /**
     * 初始化統計資料
     *
     * @param array $stat 要初始化的資料
     */
    private function init($stat)
    {
        $userId = $stat['user_id'];

        $this->rebates[$userId]['currency'] = $stat['currency'];
        $this->rebates[$userId]['domain'] = $stat['domain'];
        $this->rebates[$userId]['parent_id'] = $stat['parent_id'];
        $this->rebates[$userId]['rebate_amount'] = 0;
        $this->rebates[$userId]['rebate_count'] = 0;

        foreach ($this->rebateOpcodeMap as $column) {
            $this->rebates[$userId][$column . '_amount'] = 0;
            $this->rebates[$userId][$column . '_count'] = 0;
        }
    }

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name EntityManager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }
}
