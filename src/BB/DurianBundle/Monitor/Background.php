<?php

namespace BB\DurianBundle\Monitor;

/**
 * 監控背景
 */
class Background
{
    /**
     * @var Registry
     */
    private $container;

    /**
     * 命令名稱
     *
     * @var string
     */
    private $commandName;

    /**
     * 需要記處理筆數的命令
     *
     * @var Array
     */
    private $needSaveMsgNumCommand = [
        'run-card-poper',
        'run-card-sync',
        'run-cash-poper',
        'run-cash-sync',
        'sync-cash-negative',
        'sync-user-deposit-withdraw',
        'sync-user-api-transfer-in-out',
        'run-credit-poper',
        'run-credit-sync',
        'sync-his-poper',
        'check-cash-entry',
        'check-cash-fake-entry',
        'check-account-status',
        'toAccount',
        'message-to-italking',
        'migrate-log-operation',
        'activate-merchant',
        'activate-remit-account',
        'send-maintain-message',
        'send-message',
        'sync-cash-fake-entry',
        'sync-cash-fake-balance',
        'sync-cash-fake-history',
        'sync-cash-fake-negative',
        'sync-login-log',
        'sync-login-log-mobile',
        'stat-cash-all-offer',
        'stat-cash-deposit-withdraw',
        'stat-cash-offer',
        'stat-cash-opcode',
        'stat-cash-rebate',
        'stat-cash-remit',
        'check-redis-balance',
        'generate-rm-plan-user',
        'sync-rm-plan-user',
        'execute-rm-plan',
        'stat-domain-cash-opcode',
        'shop-web',
        'audit',
        'level-transfer',
        'remove-overdue-user',
        'activate-merchant-withdraw',
        'send-deposit-tracking-request',
        'deposit-tracking-verify',
        'stat-cash-opcode-hk',
        'stat-domain-cash-opcode-hk',
        'create-reward-entry',
        'sync-obtain-reward',
        'op-obtain-reward',
        'deposit-pay-status-error',
        'update-user-size',
        'update-level-count',
        'update-level-currency-count',
        'update-crawler-run-turn-off',
        'deposit-cancel'
    ];

    /**
     * 執行時間的上界, 若超過此時間(單位: 秒), 則代表真的很慢, 要記slow log
     *
     * @var Array
     */
    private $timeUpperBound = [
        'activate-sl-next' => 300,
        'check-card-error' => 7200,
        'check-cash-entry' => 600,
        'check-cash-error' => 7200,
        'check-cash-fake-entry' => 600,
        'check-cash-fake-error' => 1800,
        'check-account-status' => 10,
        'run-card-poper' => 2,
        'run-card-sync' => 2,
        'run-cash-poper' => 2,
        'run-cash-sync' => 2,
        'sync-cash-negative' => 2,
        'sync-user-deposit-withdraw' => 2,
        'sync-user-api-transfer-in-out' => 2,
        'run-credit-poper' => 2,
        'run-credit-sync' => 2,
        'sync-his-poper' => 2,
        'toAccount' => 10,
        'message-to-italking' => 30,
        'activate-merchant' => 10,
        'send-maintain-message' => 10,
        'sync-cash-fake-entry' => 2,
        'sync-cash-fake-balance' => 2,
        'sync-cash-fake-history' => 2,
        'sync-cash-fake-negative' => 2,
        'sync-login-log' => 2,
        'sync-login-log-mobile' => 2,
        'activate-merchant-withdraw' => 10,
        'sync-obtain-reward' => 2
    ];

    /**
     * 背景程式執行的周期
     *
     * @var Array
     */
    private $backgroundPeriod = [
        'activate-merchant'          => ['0 0 * * *'],
        'activate-remit-account'     => ['0 0 * * *'],
        'check-account-status'       => ['* * * * *'],
        'toAccount'                  => ['* * * * *'],
        'message-to-italking'        => ['* * * * *'],
        'send-message'               => ['* * * * *'],
        'check-cash-entry'           => ['0 * * * *'],
        'check-cash-fake-entry'      => ['0 * * * *'],
        'stat-cash-all-offer'        => ['10 12 * * *'],
        'stat-cash-deposit-withdraw' => ['10 12 * * *'],
        'stat-cash-offer'            => ['10 12 * * *'],
        'stat-cash-opcode'           => ['10 12 * * *'],
        'stat-cash-rebate'           => ['10 12 * * *'],
        'stat-cash-remit'            => ['10 12 * * *'],
        'check-redis-balance'        => ['30 4 * * *'],
        'sync-rm-plan-user'          => ['* * * * *'],
        'generate-rm-plan-user'      => ['* * * * *'],
        'execute-rm-plan'            => ['*/20 * * * *'],
        'stat-domain-cash-opcode'    => ['10 12 * * *'],
        'remove-overdue-user'        => ['30 2 * * *'],
        'activate-merchant-withdraw' => ['0 0 * * *'],
        'deposit-tracking-verify'    => ['*/2 * * * *'],
        'stat-cash-opcode-hk'        => ['30 0 * * *'],
        'stat-domain-cash-opcode-hk' => ['30 0 * * *'],
        'create-reward-entry'        => ['*/20 * * * *'],
        'op-obtain-reward'           => ['* * * * *'],
        'deposit-pay-status-error'   => ['* * * * *'],
        'monitor-queue-length'       => ['* * * * *'],
        'update-crawler-run-turn-off' => ['*/5 * * * *'],
        'deposit-cancel' => ['* * * * *'],
        'get-external-game-list' => ['0 6 * * *']
    ];

    /**
     * 命令開始時間
     *
     * @var string
     */
    private $beginAt;

    /**
     * 命令結束時間
     *
     * @var string
     */
    private $endAt;

    /**
     * 一次處理資料筆數
     *
     * @var int
     */
    private $msgNum = null;

    /**
     * 最後一次背景成功執行所帶入的結束時間參數
     *
     * @var \DateTime
     */
    private $lastEndTime = null;

    /**
     * 設定container
     *
     * @param Registry $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * 命令開始執行時, 需作的事
     *
     * @param string $commandName 命令名稱
     */
    public function commandStart($commandName)
    {
        $this->commandName = $commandName;
        $this->updateProcessInfoWhileBegin();

        // 更新背景開始執行時間後，即可切換回 slave
        $this->getEntityManager()->getConnection()->connect('slave');
    }

    /**
     * 命令結束時, 需作的事
     *
     * @param string $commandName 命令名稱
     */
    public function commandEnd()
    {
        $this->updateProcessInfoWhileExit();
        $this->log();
        $this->slowLog();
    }

    /**
     * 設定處理筆數
     *
     * @param int $num
     */
    public function setMsgNum($num)
    {
        $this->msgNum = $num;
    }

    /**
     * 設定背景最後一次成功執行所帶入的結束時間參數
     *
     * @param \DateTime $lastEndTime 背景最後一次成功執行所帶入的結束時間參數
     */
    public function setLastEndTime(\Datetime $lastEndTime)
    {
        $this->lastEndTime = $lastEndTime;
    }

    /**
     * 當命令開始時, 更新background_process table的資料
     *
     */
    private function updateProcessInfoWhileBegin()
    {
        $now = new \Datetime('now');
        $this->beginAt = $now->format('Y-m-d H:i:s');

        $sql = "UPDATE background_process
                SET begin_at= ?, end_at= null, num = num + 1
                WHERE name= ?";

        $params = array(
            $this->beginAt,
            $this->commandName
        );

        $this->getEntityManager()->getConnection()->executeUpdate($sql, $params);
    }

    /**
     * 當命令結束時, 更新background_process table的資料
     *
     */
    private function updateProcessInfoWhileExit()
    {
        $now = new \Datetime('now');
        $this->endAt = $now->format('Y-m-d H:i:s');
        $sql = "UPDATE background_process
                SET end_at = ?, last_end_time = ?, num = num - 1 ";

        $lastEndTime = null;
        if ($this->lastEndTime) {
            $lastEndTime = $this->lastEndTime->format('Y-m-d H:i:s');
        }

        $params = [
            $this->endAt,
            $lastEndTime
        ];

        if ($this->needSaveMsgNum()) {
            $sql .= ", msg_num= ? ";
            $params[] = $this->msgNum;
        }

        $sql .= "WHERE name= ?";
        $params[] = $this->commandName;

        $this->getEntityManager()->getConnection()->executeUpdate($sql, $params);
    }

    /**
     * 判斷此命令是否需要記錄處理資料的筆數
     *
     * @return bool
     */
    private function needSaveMsgNum()
    {
        return in_array($this->commandName, $this->needSaveMsgNumCommand);
    }

    /**
     * 記錄log
     */
    private function log()
    {
        $commandName = $this->commandName;
        $logFile = 'background_process' . DIRECTORY_SEPARATOR . str_replace('-', '_', $commandName) . '.log';
        $logger = $this->container->get('durian.logger_manager')->setUpLogger($logFile);

        $msg = "'{$this->beginAt}', '{$this->endAt}', '{$this->msgNum}'";
        $logger->addInfo($msg);
        $logger->popHandler()->close();
    }

    /**
     * 記錄slowlog
     */
    private function slowLog()
    {
        if (!$this->needSlowLog()) {
            return;
        }

        if ((strtotime($this->endAt) - strtotime($this->beginAt)) < $this->getTimeUpperBound()) {
            return;
        }

        $commandName = $this->commandName;
        $logFile = 'background_process' . DIRECTORY_SEPARATOR . str_replace('-', '_', $commandName) . '.slow.log';
        $logger = $this->container->get('durian.logger_manager')->setUpLogger($logFile);

        $msg = "'{$this->beginAt}', '{$this->endAt}', '{$this->msgNum}'";
        $logger->addInfo($msg);
        $logger->popHandler()->close();
    }

    /**
     * 判斷此命令是否需要記slow log
     *
     * @return bool
     */
    private function needSlowLog()
    {
        return array_key_exists($this->commandName, $this->timeUpperBound);
    }

    /**
     * 取得此命令執行時間的上界, 若超過此上界, 則要記slow log
     *
     * @return int 此命令執行時間的上界, 單位: 秒
     */
    private function getTimeUpperBound()
    {
        if (!$this->needSlowLog()) {
            return 99999999;
        }

        return $this->timeUpperBound[$this->commandName];
    }

    /**
     * 取得此背景執行時間的上界
     *
     * @param string $name 背景程式名稱
     *
     * @return int 此命令執行時間的上界, 單位: 秒
     */
    public function getTimeUpperBoundByName($name)
    {
        if (!(array_key_exists($name, $this->timeUpperBound))) {
            return 86400;
        }

        return $this->timeUpperBound[$name];
    }

    /**
     * 依帶入時間為準，取得下一次預計執行此背景的時間
     *
     * @param string $name         背景程式名稱
     * @param string $relativeDate 依據時間
     *
     * @return int
     */
    public function getNextExpectedTime($name, $relativeDate)
    {
        if ($name == 'activate-sl-next') {
            $sql = "SELECT period FROM share_update_cron";
            $executionTime = $this->getEntityManager()->getConnection()->fetchAll($sql);

            $this->backgroundPeriod['activate-sl-next'] = [];
            foreach ($executionTime as $time) {
                $this->backgroundPeriod['activate-sl-next'][] = $time['period'];
            }
        }

        if ($name == 'send-maintain-message' || $name == 'send-immediate-message') {
            return strtotime($relativeDate) + 20;
        }

        if ($name == 'send-deposit-tracking-request') {
            return strtotime($relativeDate) + 5;
        }

        // 處理每秒執行的背景程式
        if (!(array_key_exists($name, $this->backgroundPeriod))) {
            return strtotime($relativeDate) + 1;
        }

        $cron = \Cron\CronExpression::factory($this->backgroundPeriod[$name][0]);

        return $cron->getNextRunDate($relativeDate, 0, false)->getTimestamp();
    }

    /**
     * 修正已卡住的執行數量
     *
     * @param string $name 程式名稱
     * @param int $num 執行數量
     */
    public function setBgProcessNum($name, $num)
    {
        $sql = "UPDATE background_process
                SET num = num + ?
                WHERE name= ?";

        $params = [
            $num,
            $name
        ];

        $em = $this->getEntityManager();
        $em->getConnection()->executeUpdate($sql, $params);
    }

    /**
     * 修正啟用狀態
     *
     * @param string $name 程式名稱
     * @param bool $enable 啟用狀態
     */
    public function setBgProcessEnable($name, $enable)
    {
        $sql = "UPDATE background_process
                SET enable = ?
                WHERE name= ?";

        $params = [
            $enable,
            $name
        ];

        $em = $this->getEntityManager();
        $em->getConnection()->executeUpdate($sql, $params);
    }

    /**
     * 回傳EntityManager
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }
}
