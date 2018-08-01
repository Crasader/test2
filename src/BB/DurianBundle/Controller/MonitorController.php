<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class MonitorController extends Controller
{
    /**
     * 紀錄queue的種類
     */
    private $queueType = [
        'cash'             => 'cashQueue',
        'cash_fake'        => 'cashFakeQueue',
        'card'             => 'cardQueue',
        'credit'           => 'creditQueue',
        'italking'         => 'italkingQueue',
        'rm_plan_user'     => 'rmPlanUserQueue',
        'message'          => 'messageQueue',
        'deposit'          => 'depositQueue',
        'shopweb'          => 'shopWebQueue',
        'audit'            => 'auditQueue',
        'login_log'        => 'loginLogQueue',
        'reward'           => 'rewardQueue',
        'kue'              => 'kueQueue',
        'user_size'        => 'userSizeQueue',
        'level'            => 'levelQueue',
        'bodog'            => 'bodogQueue',
        'external'         => 'externalQueue',
        'suncity'          => 'suncityQueue',
        'merchant_rsa_key' => 'merchantRsaKeyQueue',
        'auto_withdraw'    => 'autoWithdrawQueue',
        'set_withdraw_status' => 'setWithdrawStatusQueue',
    ];

    /**
     * cash Queue細項
     */
    private $cashQueue = [
        'queue'                         => '現金明細、交易',
        'retry_queue'                   => '現金明細、交易(重試)',
        'failed_queue'                  => "現金明細、交易(失敗)。\n大於零，請執行 durian:run-cash-poper --recover-fail",
        'entry_queue'                   => '現金歷史明細',
        'entry_retry_queue'             => '現金歷史明細(重試)',
        'entry_failed_queue'            => "現金歷史明細(失敗)。 \n大於零，請執行 durian:sync-his-poper --recover-fail",
        'deposit_withdraw_queue'        => '現金存提款紀錄',
        'deposit_withdraw_retry_queue'  => '現金存提款紀錄(重試)',
        'deposit_withdraw_failed_queue' => "現金存提款紀錄(失敗)。 \n大於零，請執行 durian:sync-user-deposit-withdraw --recover-fail",
        'sync_queue0'                   => '現金同步佇列0餘額',
        'sync_retry_queue0'             => '現金同步佇列0餘額(重試)',
        'sync_failed_queue0'            => "現金同步佇列0餘額(失敗)。 \n大於零，請執行 durian:run-cash-sync --executeQueue=0 --recover-fail",
        'sync_queue1'                   => '現金同步佇列1餘額',
        'sync_retry_queue1'             => '現金同步佇列1餘額(重試)',
        'sync_failed_queue1'            => "現金同步佇列1餘額(失敗)。 \n大於零，請執行 durian:run-cash-sync --executeQueue=1 --recover-fail",
        'sync_queue'                    => '現金餘額',
        'negative_queue'                => '現金同步負數餘額'
    ];

    /**
     * cashFake Queue細項
     */
    private $cashFakeQueue = [
        'entry_queue' => '快開額度明細',
        'entry_queue_retry' => '快開額度明細(重試)',
        'entry_queue_failed' => "快開額度明細(失敗)。\n大於零，請執行 durian:sync-cash-fake --entry --recover-fail",
        'transfer_queue' => '快開額度轉帳明細',
        'transfer_queue_retry' => '快開額度轉帳明細(重試)',
        'transfer_queue_failed' => "快開額度轉帳明細(失敗)。\n大於零，請執行 durian:sync-cash-fake --entry --recover-fail",
        'operator_queue' => '快開額度明細操作者',
        'operator_queue_retry' => '快開額度明細操作者(重試)',
        'operator_queue_failed' => "快開額度明細操作者(失敗)。\n大於零，請執行 durian:sync-cash-fake --entry --recover-fail",
        'history_queue' => '快開額度歷史明細',
        'history_queue_retry' => '快開額度歷史明細(重試)',
        'history_queue_failed' => "快開額度歷史明細(失敗)。\n大於零，請執行 durian:sync-cash-fake --history --recover-fail",
        'trans_queue' => '快開額度新增交易',
        'trans_queue_retry' => '快開額度新增交易(重試)',
        'trans_queue_failed' => "快開額度新增交易(失敗)。\n大於零，請執行 durian:sync-cash-fake --entry --recover-fail",
        'trans_update_queue' => '快開額度更新交易',
        'trans_update_queue_retry' => '快開額度更新交易(重試)',
        'trans_update_queue_failed' => "快開額度更新交易(失敗)。\n大於零，請執行 durian:sync-cash-fake --entry --recover-fail",
        'balance_queue' => '快開額度同步餘額',
        'balance_queue_retry' => '快開額度同步餘額(重試)',
        'balance_queue_failed' => '快開額度同步餘額(失敗)',
        'negative_queue' => '快開額度同步負數餘額',
        'api_transfer_in_out_queue' => '快開api轉入轉出同步記錄',
        'api_transfer_in_out_queue_retry' => '快開api轉入轉出同步記錄(重試)',
        'api_transfer_in_out_queue_failed' => "快開api轉入轉出同步記錄(失敗)。\n大於零，請執行 durian:sync-cash-fake --api-transfer-in-out --recover-fail"
    ];

    /**
     * card Queue細項
     */
    private $cardQueue = array(
        'queue'             => '租卡明細',
        'retry_queue'       => '租卡明細(重試)',
        'failed_queue'      => "租卡明細(失敗)。\n大於零，請執行 durian:run-card-poper --recover-fail",
        'sync_queue'        => '租卡同步餘額',
        'sync_retry_queue'  => '租卡同步餘額(重試)',
        'sync_failed_queue' => '租卡同步餘額(失敗)',
    );

    /**
     * credit Queue細項
     */
    private $creditQueue = [
        'queue'               => '信用額度資料',
        'queue_retry'         => '信用額度資料(重試)',
        'queue_failed'        => "信用額度資料(失敗)。\n大於零，請執行 durian:sync-credit --credit --recover-fail",
        'period_queue'        => '累積交易金額資料',
        'period_queue_retry'  => '累積交易金額資料(重試)',
        'period_queue_failed' => "累積交易金額資料(失敗)。\n大於零，請執行 durian:sync-credit --period --recover-fail",
        'entry_queue'         => '信用額度明細資料',
        'entry_queue_retry'   => '信用額度明細資料(重試)',
        'entry_queue_failed'  => "信用額度明細資料(失敗)。\n大於零，請執行 durian:sync-credit --entry --recover-fail"
    ];

    /**
     * italking Queue細項
     */
    private $italkingQueue = [
        'message_queue'   => 'ITalking 訊息佇列',
        'exception_queue' => 'ITalking 傳送例外訊息'
    ];

    /**
     * rm plan user Queue細項
     */
    private $rmPlanUserQueue = [
        'queue' => '紀錄刪除計畫使用者的佇列'
    ];

    /*
     * message Queue細項
     */
    private $messageQueue = [
        'queue'                  => '非即時訊息',
        'queue_retry'            => '非即時訊息(重試)',
        'queue_failed'           => '非即時訊息(失敗)',
        'immediate_queue'        => '即時訊息',
        'immediate_queue_retry'  => '即時訊息(重試)',
        'immediate_queue_failed' => '即時訊息(失敗)'
    ];

    /**
     * deposit Queue細項
     */
    private $depositQueue = [
        'pay_status_error_queue' => '紀錄異常入款錯誤的佇列',
    ];

    /**
     * shop web Queue細項
     */
    private $shopWebQueue = [
        'queue' => '要發送購物網通知的佇列'
    ];

    /**
     * audit Queue細項
     */
    private $auditQueue = [
        'queue' => '要通知稽核的佇列'
    ];

    /**
     * login log Queue細項
     */
    private $loginLogQueue = [
        'queue'               => '登入紀錄',
        'queue_retry'         => '登入紀錄(重試)',
        'queue_failed'        => "登入紀錄(失敗)。\n大於零，請執行 durian:sync-login-log --recover-fail",
        'mobile_queue'        => '登入紀錄行動裝置資訊',
        'mobile_queue_retry'  => '登入紀錄行動裝置資訊(重試)',
        'mobile_queue_failed' => "登入紀錄行動裝置資訊(失敗)。\n大於零，請執行 durian:sync-login-log --recover-fail"
    ];

    /**
     * reward Queue細項
     */
    private $rewardQueue = [
        'entry_created_queue' => '待建立紅包明細的佇列',
        'sync_queue'          => '待同步明細的佇列',
        'op_queue'            => '待派彩的佇列'
    ];

    /**
     * kue Queue細項
     */
    private $kueQueue = [
        'inactive' => '尚未執行的佇列',
        'complete' => '已完成的佇列',
        'active'   => '執行中的佇列',
        'failed'   => '失敗的佇列'
    ];

    /**
     * user size Queue細項
     */
    private $userSizeQueue = [
        'queue' => '使用者下層數量'
    ];

    /**
     * level Queue細項
     */
    private $levelQueue = [
        'user_count_queue' => '層級會員人數',
        'currency_user_count_queue' => '層級幣別會員人數',
        'transfer_queue' => '待層級轉移數量'
    ];

    /**
     * bodog Queue細項
     */
    private $bodogQueue = [
        'queue'                    => '待同步佇列',
        'retry_queue'              => '重試佇列',
        'failed_queue'             => '失敗的佇列，請執行 node run_bodog_poper_command.js --recover-fail',
        'live_payoff_queue'        => '視訊待派彩的佇列',
        'live_payoff_commit_queue' => '視訊待派彩確認的佇列',
        'live_payoff_failed_queue' => '視訊派彩失敗的佇列'
    ];

    /**
     * external Queue細項
     */
    private $externalQueue = [
        'op_queue'        => '交易失敗待處理佇列',
        'op_retry_queue'  => '交易失敗待重試佇列',
        'op_failed_queue' => '失敗的佇列，請執行 node sync_exteranl_op_queue_command.js --recover-fail'
    ];

    /**
     * suncity Queue細項
     */
    private $suncityQueue = [
        'queue'                => '待同步佇列',
        'retry_queue'          => '重試佇列',
        'failed_queue'         => '失敗的佇列，請執行 node run_suncity_poper_command.js --recover-fail',
        'live_op_queue'        => '視訊待派彩的佇列',
        'live_commit_queue'    => '視訊待派彩確認的佇列',
        'live_op_failed_queue' => '視訊派彩失敗的佇列'
    ];

    /**
     * merchant rsa key Queue細項
     */
    private $merchantRsaKeyQueue = [
        'queue' => '待檢查商號公私鑰的佇列'
    ];

    /**
     * DB中需監控的table名稱
     */
    private $tableName = [
        'cash_entry_diff'      => '紀錄現行及歷史現金差異的差異資料',
        'cash_fake_entry_diff' => '紀錄現行及歷史快開額度差異的資料',
        'card_error'           => '租卡額度不符',
        'cash_error'           => '現金額度不符',
        'cash_fake_error'      => '快開額度不符'
    ];

    /**
     * 執行時間浮動的工作
     */
    private $floatingWork = [
        'check-card-error',
        'check-cash-error',
        'check-cash-fake-error',
        'migrate-log-operation'
    ];

    /**
     * autoWithdraw Queue細項
     */
    private $autoWithdrawQueue = [
        'queue' => '尚未執行出款請求的佇列'
    ];

    /**
     * setWithdrawStatus Queue細項
     */
    private $setWithdrawStatusQueue = [
        'queue' => '失敗待處理佇列',
        'queue_retry' => '失敗待重試佇列',
        'queue_failed' => '失敗的佇列，請執行 --recover-fail'
    ];

    /**
     * Monitor index page
     *
     * @Route("/monitor", name = "monitor")
     * @return Renders
     */
    public function indexAction()
    {
        return $this->render(
            'BBDurianBundle:Default:monitor.html.twig'
        );
    }

    /**
     * 回傳background資訊
     *
     * @Route("/api/monitor/background", name = "api_monitor_background")
     *
     * @return JsonResponse
     */
    public function backgroundAction()
    {
        $em = $this->getDoctrine()->getManager('default');
        $backgrounds = $em->getRepository('BBDurianBundle:BackgroundProcess')->findAll();

        $monitorInfo = array();

        foreach ($backgrounds as $background) {
            $name     = $background->getName();
            $beginAt  = $background->getBeginAt()->getTimestamp();
            $endAt    = $background->getEndAt();
            $memo     = $background->getMemo();
            $enable   = $background->isEnable();
            $bgNum    = $background->getNum();
            $bgMsgNum = $background->getMsgNum();

            if ($endAt) {
                $endAt = $endAt->getTimestamp();
            }

            $executionTime = $this->bgExecutionTime($beginAt, $endAt);

            $status = $this->bgStatus($name, $executionTime, $beginAt, $enable, $bgNum);

            $hour = intval($executionTime / 3600);
            $minute = intval(($executionTime % 3600) / 60);
            $second = ($executionTime % 3600) % 60;

            $time = "";

            if ($hour) {
                $time .= $hour . "時";
            }
            if ($minute) {
                $time .= $minute . "分";
            }
            if (isset($second)) {
                $time .= $second . "秒";
            }

            $endAtStr = '';
            if ($endAt) {
                $endAtStr = date('Y-m-d H:i:s', $endAt);
            }

            $monitorInfo[] = array(
                'name'     => $name,
                'memo'     => $memo,
                'beginAt'  => date('Y-m-d H:i:s', $beginAt),
                'endAt'    => $endAtStr,
                'time'     => $time,
                'bgNum'    => $bgNum,
                'bgMsgNum' => $bgMsgNum,
                'status'   => $status
            );
        }

        $output['result'] = 'ok';
        $output['ret'] = $monitorInfo;

        return new JsonResponse($output);
    }

    /**
     * 回傳DB資訊
     *
     * @Route("/api/monitor/database", name = "api_monitor_database")
     *
     * @return JsonResponse
     */
    public function databaseAction()
    {
        foreach ($this->tableName as $tableName => $memo) {
            $entityName = \Doctrine\Common\Util\Inflector::classify($tableName);

            $em = $this->getDoctrine()->getManager();
            if (strpos($tableName, 'error')) {
                $em = $this->getDoctrine()->getManager('share');
            }

            $dataNum = $em->getRepository("BBDurianBundle:$entityName")
                          ->countNumOf();

            $status = 'normal';
            if ($dataNum > 0) {
                $status = 'abnormal';
            }

            $monitorInfo[] = array(
                'name'     => $tableName,
                'number'   => $dataNum,
                'status'   => $status,
                'memo'     => $memo
            );
        }

        $output['result'] = 'ok';
        $output['ret'] = $monitorInfo;

        return new JsonResponse($output);
    }

    /**
     * 回傳queue資訊
     *
     * @Route("/api/monitor/queue", name = "api_monitor_queue")
     *
     * @return JsonResponse
     */
    public function queueAction()
    {
        $redisDefault = $this->container->get('snc_redis.default');
        $redisReward = $this->container->get('snc_redis.reward');
        $redisKue = $this->container->get('snc_redis.kue');
        $redisBodog = $this->container->get('snc_redis.bodog');
        $redisExternal = $this->container->get('snc_redis.external');
        $redisSuncity = $this->container->get('snc_redis.suncity');

        $monitorInfo = array();

        foreach ($this->queueType as $type => $queue) {
            $noHost = false;
            $redis = $redisDefault;

            $extraRedis = [
                'reward'   => $redisReward,
                'kue'      => $redisKue,
                'bodog'    => $redisBodog,
                'external' => $redisExternal,
                'suncity'  => $redisSuncity
            ];

            if (array_key_exists($type, $extraRedis)) {
                $redis = $extraRedis[$type];

                $ip = $this->container->getParameter("redis_$type");

                if (strpos($ip, 'null')) {
                    $noHost = true;
                }
            }

            foreach ($this->$queue as $name => $memo) {
                $queueName = "{$type}_{$name}";
                $redisFn = 'llen';

                if ($type == 'kue') {
                    $queueName = 'jobs:' . $name;
                    $redisFn = 'zcard';
                }

                $count = 'null';

                if (!$noHost) {
                    $count = $redis->$redisFn($queueName);
                }

                $monitorInfo[] = [
                    'name'     => $name,
                    'queueNum' => $count,
                    'type'     => $type,
                    'memo'     => $memo
                ];
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = $monitorInfo;

        return new JsonResponse($output);
    }

    /**
     * 判斷背景程式的狀態
     *
     * @param string  $name
     * @param int     $executionTime
     * @param int     $beginAt
     * @param boolean $enable
     * @param int     $bgNum
     *
     * @return string
     */
    private function bgStatus($name, $executionTime, $beginAt, $enable, $bgNum)
    {
        if (!$enable) {
            return 'disable';
        }

        $bgMonitor = $this->get('durian.monitor.background');
        $now = new \DateTime();

        //給予背景程式一個緩衝時間延遲執行,判斷是否沒有執行
        $bufferTime = 3;

        // ex. 當前時間為2015-01-26 10:31:00，應執行時間為2015-01-26 10:30:00，延遲時間則為60
        $delayTime = $now->getTimestamp() - $bgMonitor->getNextExpectedTime($name, date('Y-m-d H:i:s', $beginAt));

        // 延遲在3秒內為正常
        $isExecuted = $delayTime <= $bufferTime;

        // 因 execute-rm-plan 不一定是在整點、20分、40分時執行，改判斷延遲時間不超過20分鐘即可
        if ($name == 'execute-rm-plan') {
            $isExecuted = $delayTime <= 1200;
        }

        // monitor-stat 用統計背景的下次執行時間來計算背景狀態
        if ($name == 'monitor-stat') {
            $isExecuted = true;

            // 美東時間統計背景
            $delayTimeUS = $now->getTimestamp() - $bgMonitor->getNextExpectedTime('stat-cash-opcode', date('Y-m-d H:i:s', $beginAt));

            // 香港時間統計背景
            $delayTimeHK = $now->getTimestamp() - $bgMonitor->getNextExpectedTime('stat-cash-opcode-hk', date('Y-m-d H:i:s', $beginAt));

            if ($delayTimeUS > $bufferTime || $delayTimeHK > $bufferTime) {
                $isExecuted = false;
            }
        }

        // 判斷執行時間浮動的工作是否有執行
        if (in_array($name, $this->floatingWork)) {
            // 檢查系統時間 24 小時內，該浮動工作有執行過為正常
            $isExecuted = $now->getTimestamp() >= $beginAt && $now->getTimestamp() - $beginAt < 86400;
        }

        if (!$isExecuted) {
            return 'noExecuted';
        }

        if ($executionTime >= $bgMonitor->getTimeUpperBoundByName($name)) {
            return 'executedTooLong';
        }

        if ($bgNum != 0) {
            return 'executing';
        }

        return 'normal';
    }

    /**
     * 計算背景程式的執行時間
     *
     * @param int $beginAt  執行開始時間
     * @param int $endAt    執行結束時間
     *
     * @return int
     */
    private function bgExecutionTime($beginAt, $endAt)
    {
        $now = new \DateTime();

        if (!$endAt) {
            return $now->getTimeStamp() - $beginAt;
        }

        $executionTime = $endAt - $beginAt;

        return $executionTime;
    }
}
