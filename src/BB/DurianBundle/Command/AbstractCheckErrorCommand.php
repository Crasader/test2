<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 用來檢查額度不符名單之物件
 */
abstract class AbstractCheckErrorCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * 時間區間開始的日期
     *
     * @var \DateTime
     */
    private $beginDate = null;

    /**
     * 時間區間結束的日期
     *
     * @var \DateTime
     */
    private $endDate = null;

    /**
     * 儲存明細小計的結果
     *
     * @var array
     */
    private $summary;

    /**
     * 儲存有錯誤的資料
     *
     * @var array
     */
    private $balanceError;

    /**
     * 僅顯示錯誤資料，不儲存至資料庫(預設: false)
     *
     * @var boolean
     */
    private $dryRun;

    /**
     * 確認最後一筆明細是否遺失
     *
     * @var boolean
     */
    private $checkLast;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // 初始化相關變數
        $this->dryRun = false;
        $this->output = $output;

        $this->getOpt($input);

        $entityName = \Doctrine\Common\Util\Inflector::tableize($this->getEntityName());
        $entityName = str_replace('_', '-', $entityName);

        $command = sprintf('check-%s-error', $entityName);

        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart($command);

        $this->printStartMsg();

        $maxEnd = $this->endDate;
        $intervalTime = new \DateInterval('PT1M');

        /**
         * 調整時間:
         * 1. beginAt 為 beginDate
         * 2. endAt   為 beginDate + intervalTime
         */
        $beginAt = clone $this->beginDate;
        $endAt   = clone $this->beginDate;
        $endAt->add($intervalTime);

        $this->summary = [];
        $this->balanceError = [];

        while ($beginAt < $maxEnd) {
            $this->processEntryByTime($beginAt, $endAt);

            $beginAt->add($intervalTime);
            $endAt->add($intervalTime);
        }

        $this->processEntryByVersionInterval();
        $this->processOnlyOneEntry();

        $this->checkError();

        if ($this->checkLast) {
            $this->checkLastEntry();
        }

        if (!$this->dryRun) {
            $this->storeError();
        }

        $this->printExitMsg();

        $bgMonitor->setLastEndTime($this->endDate);
        $bgMonitor->commandEnd();
    }

    /**
     * 檢查額度不符的資料
     */
    protected function checkError()
    {
        foreach ($this->summary as $majorKey => $entry) {

            // 忽略只有一筆交易明細，無法驗證
            if (1 === $entry['count']) {
                continue;
            }

            $finalBalance = round($entry['final_balance'], $this->getNumberOfDecimalPlaces());
            $nowBalance   = round(
                $entry['first_balance'] + $entry['amount'],
                $this->getNumberOfDecimalPlaces()
            );

            if ($nowBalance != $finalBalance) {
                $this->balanceError[$majorKey] = $entry;

                $msg = $this->getErrorMessage($majorKey, $entry);
                $this->output->write($msg, true);
            }
        }
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     *
     * @param string $name EntityManager 名稱
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 取得時間參數
     *
     * @throws \Exception
     */
    protected function getOpt(InputInterface $input)
    {
        $this->dryRun = $input->getOption('dry-run');

        if ($this->getEntityName() == 'Cash' || $this->getEntityName() == 'CashFake') {
            $this->checkLast = $input->getOption('check-last');
        }

        // 時間區間, 預設為前一小時整
        $begin = date('Y-m-d H', time() - 3600) . ':00:00';
        $end = date('Y-m-d H') . ':00:00';

        $optBegin = $input->getOption('begin');
        $optEnd = $input->getOption('end');

        if (($optBegin && !$optEnd) || (!$optBegin && $optEnd)) {
            throw new \Exception('需同時指定開始及結束時間');
        }

        if ($optBegin) {
            $begin = $optBegin;
        }

        if ($optEnd) {
            $end = $optEnd;
        }

        $this->beginDate = new \DateTime($begin);
        $this->endDate = new \DateTime($end);

        $diffSecond = $this->endDate->getTimestamp() - $this->beginDate->getTimestamp();

        if ($diffSecond > 86400) {
            throw new \Exception('請避免處理超過一天的資料');
        }

        if ($diffSecond <= 0) {
            throw new \Exception('無效的開始及結束時間');
        }
    }

    /**
     * 輸出完成訊息
     */
    protected function printExitMsg()
    {
        $date = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $dateStr = $date->format(\DateTime::ISO8601);
        $this->output->write("{$dateStr} : Check{$this->getEntityName()}ErrorCommand end...", true);
    }

    /**
     * 輸出起始訊息
     */
    protected function printStartMsg()
    {
        $date = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $dateStr = $date->format(\DateTime::ISO8601);
        $this->output->write("{$dateStr} : Check{$this->getEntityName()}ErrorCommand begin...", true);
    }

    /**
     * 計算只有一筆交易明細的明細小計
     * 會往前查詢一筆資料
     */
    protected function processOnlyOneEntry()
    {
        // 將結束時間往後拉一分鐘確保明細沒有延遲到區間外的情形
        $endDate = clone $this->endDate;
        $end = $endDate->add(new \DateInterval('PT1M'))->format('YmdHis');

        foreach ($this->summary as $majorKey => $entry) {
            if (1 !== $entry['count']) {
                continue;
            }

            // 因 redis 交易順序不一定會跟 id 順序相符, 有可能會有 version 較小但是 id 卻比較大的情況
            // 所以這邊將 id 往後加 10000 確保不會取錯明細, 且須比較版本號
            $id = $entry['final_id'] + 10000;
            $version = $entry['min_version'];

            $prevEntry = $this->getPrevEntry($majorKey, $id, $version, $end, false);

            if (!$prevEntry) {
                continue;
            }

            $firstBalance = round(
                $prevEntry['balance'] - $prevEntry['amount'],
                $this->getNumberOfDecimalPlaces()
            );
            $amount = round(
                $prevEntry['amount'] + $entry['amount'],
                $this->getNumberOfDecimalPlaces()
            );

            $entry['first_balance'] = $firstBalance;
            $entry['amount'] = $amount;
            $entry['count']++;

            $this->summary[$majorKey] = $entry;
        }
    }

    /**
     * 當有兩筆以上的明細時
     * 確認區間外前後各一分鐘是否有遺漏的明細, 有就將額度加上
     */
    protected function processEntryByVersionInterval()
    {
        // 檢查區間有兩段, 分別為 $this->beginDate 往前推一分鐘及 $this->endDate 往後一分鐘
        $begin1 = clone $this->beginDate;
        $begin1->sub(new \DateInterval('PT1M'));
        $end1 = clone $this->beginDate;

        $begin2 = clone $this->endDate;
        $end2 = clone $this->endDate;
        $end2->add(new \DateInterval('PT1M'));

        $entries1 = $this->getEntryByTimeInterval($begin1, $end1);
        $entries2 = $this->getEntryByTimeInterval($begin2, $end2);

        $entries = array_merge($entries1, $entries2);

        if (!$entries) {
            return;
        }

        foreach ($entries as $entry) {
            $majorKey = $entry['major_key'];

            $amount  = round($entry['amount'], $this->getNumberOfDecimalPlaces());

            // major_key 不存在則不處理
            if (!isset($this->summary[$majorKey])) {
                continue;
            }

            // 若新明細 version 不在 min_version/max_version 區間內則不處理
            if ($entry['version'] < $this->summary[$majorKey]['min_version'] ||
                $entry['version'] > $this->summary[$majorKey]['max_version']) {
                continue;
            }

            $this->summary[$majorKey]['count']++;
            $this->summary[$majorKey]['amount'] += $amount;
        }
    }

    /**
     * 儲存額度不符名單
     */
    protected function storeError()
    {
        foreach ($this->balanceError as $majorKey => $entry) {

            $msg = $this->getErrorMessage($majorKey, $entry);

            $this->saveErrorEntry($majorKey, $entry);

            $italkingOperator = $this->getContainer()->get('durian.italking_operator');
            $queueMsg = sprintf('有%s差異, 請檢查%s', $this->getEntityName(true), $msg);
            $italkingOperator->pushMessageToQueue('developer_acc', $queueMsg);
        }
    }

    /**
     * 給定時間區間，計算各 major_key 的明細小計
     *
     * @param \DateTime $beginAt 起始時間
     * @param \DateTime $endAt   終止時間
     */
    protected function processEntryByTime($beginAt, $endAt)
    {
        $entries = $this->getEntryByTimeInterval($beginAt, $endAt);

        if (!$entries) {
            return;
        }

        foreach ($entries as $entry) {
            $majorKey = $entry['major_key'];

            $amount  = round($entry['amount'], $this->getNumberOfDecimalPlaces());
            $balance = round($entry['balance'], $this->getNumberOfDecimalPlaces());
            $firstBalance = round($balance - $amount, $this->getNumberOfDecimalPlaces());

            // 第一次沒資料就先儲存
            if (!isset($this->summary[$majorKey])) {
                $this->summary[$majorKey] = [
                    'count' => 1,
                    'amount' => $amount,
                    'first_balance' => $firstBalance,
                    'final_balance' => $balance,
                    'final_id' => $entry['id'],
                    'min_version' => $entry['version'],
                    'max_version' => $entry['version'],
                ];
                continue;
            }

            // 若新明細 version 比紀錄的 min_version 還要小, 則更新 first_balance
            if ($entry['version'] < $this->summary[$majorKey]['min_version'] ) {
                $this->summary[$majorKey]['min_version'] = $entry['version'];
                $this->summary[$majorKey]['first_balance'] = $firstBalance;
                $this->summary[$majorKey]['count']++;
                $this->summary[$majorKey]['amount'] += $amount;
                $this->summary[$majorKey]['amount'] = round(
                    $this->summary[$majorKey]['amount'],
                    $this->getNumberOfDecimalPlaces()
                );
                continue;
            }

            // 若新明細 version 比紀錄的 max_version 還要小, 則不更新 final_balance 及 final_id
            if ($entry['version'] < $this->summary[$majorKey]['max_version'] ) {
                $this->summary[$majorKey]['count']++;
                $this->summary[$majorKey]['amount'] += $amount;
                $this->summary[$majorKey]['amount'] = round(
                    $this->summary[$majorKey]['amount'],
                    $this->getNumberOfDecimalPlaces()
                );
                continue;
            }

            $this->summary[$majorKey]['count']++;
            $this->summary[$majorKey]['amount'] += $amount;
            $this->summary[$majorKey]['amount'] = round(
                $this->summary[$majorKey]['amount'],
                $this->getNumberOfDecimalPlaces()
            );
            $this->summary[$majorKey]['final_balance'] = $balance; // 儲存最後一筆餘額
            $this->summary[$majorKey]['final_id'] = $entry['id']; // 儲存最後一筆ID
            $this->summary[$majorKey]['max_version'] = $entry['version']; // 儲存最後一筆version
        }
    }

    /**
     * 檢查明細是否遺漏最後一筆資料
     */
    protected function checkLastEntry()
    {
        $startId = 0;

        $beginAt = clone $this->beginDate;
        $endAt = clone $this->endDate;

        while (true) {
            // 分批撈出符合條件的cash或cashFake
            $paywayArray = $this->getHasEntryData($beginAt, $endAt, $startId);

            if (!$paywayArray) {
                break;
            }

            foreach ($paywayArray as $payway) {
                $id = $payway['id'];
                $userId = $payway['user_id'];
                $at = $payway['last_entry_at'];
                $version = $payway['version'];
                $currency = $payway['currency'];
                $balance = $payway['balance'];

                $lastEntry = $this->getLastEntryByTime($at, $userId);
                $majorKey = sprintf('%s/%s/%s', $id, $userId, $currency);

                // 如果lastEntry為空，表示該使用者額度遺失，需撈出前一筆明細，以用來比對遺失的是否為派彩明細
                if (!$lastEntry) {
                    $lastEntry = $this->getPrevEntry($majorKey, PHP_INT_MAX, PHP_INT_MAX, $at);
                }

                // 如果仍然沒有明細，則代表遺漏該使用者的第一筆明細。如果最大明細版號小於現金或假現金版號，表示明細遺失。
                if (!$lastEntry || $lastEntry['version'] < $version) {
                    $this->verifyReason($userId, $currency, $lastEntry);

                    $payway['amount'] = $balance;
                    $payway['final_balance'] = $balance;

                    $this->balanceError[$majorKey] = $payway;

                    $msg = $this->getErrorMessage($majorKey, $payway);
                    $this->output->write($msg, true);
                }
            }

            $startId = $id;
        }
    }

    /**
     * 確認明細遺漏原因
     *
     * @param integer $userId   使用者ID
     * @param integer $currency 幣別
     * @param array   $entry    明細資料
     */
    protected function verifyReason($userId, $currency, $entry)
    {
        if (!$entry) {
            $this->output->writeln("LoseLastEntry: userId: {$userId}, no entry in mysql");

            return;
        }

        $plusNumber = 10000;

        $maxVersion = $entry['version'];
        $balance = $entry['balance'];

        $redis = $this->getRedis($userId);
        $key = $this->getRedisKey($userId, $currency);

        $redis->multi();
        $redis->hget($key, 'balance');
        $redis->hget($key, 'version');
        $results = $redis->exec();

        $redisBalance = $results[0] / $plusNumber;
        $redisVersion = $results[1];

        // 若redis與資料庫餘額不相符，則輸出redis和最大明細的餘額
        if ($redisBalance != $balance) {
            $this->output->writeln("LoseLastEntry: userId: {$userId}, redis balance: {$redisBalance}, max version entry balance: {$balance}");
        }

        // 若redis餘額與資料庫餘額相符，且只遺失單筆明細，可能是遺失派彩明細，若遺失多筆明細，可能是遺失明細的交易金額加總恰巧為0的狀況。
        if ($redisBalance == $balance) {
            $this->output->writeln("LoseLastEntry: userId: {$userId}, redis balance same as max version entry balance. redis version: {$redisVersion}, entry max version: {$maxVersion}");
        }
    }

    /**
     * 取得entity名稱
     *
     * @param boolean $isChinese 是否回傳中文名稱
     * @return string
     */
    abstract protected function getEntityName($isChinese);

    /**
     * 取得小數位數
     *
     * @return integer
     */
    abstract protected function getNumberOfDecimalPlaces();

    /**
     * 新增錯誤資訊
     *
     * @param string $majorKey 複合主鍵
     * @param array  $entry    明細小計結果
     */
    abstract protected function saveErrorEntry($majorKey, $entry);

    /**
     * 取得錯誤訊息
     *
     * @param string $majorKey 複合主鍵
     * @param array  $entry    明細小計結果
     * @return string
     */
    abstract protected function getErrorMessage($majorKey, $entry);

    /**
     * 往前搜尋一筆交易明細
     *
     * @param string  $majorKey 複合主建
     * @param integer $id       明細編號
     * @param integer $version  明細版本號
     * @param integer $at       時間
     * @param boolean $history  採用歷史資料庫搜尋
     * @return Array | null
     */
    abstract protected function getPrevEntry($majorKey, $id, $version, $at, $history = false);


    /**
     * 給定時間區間內的 entry 資料
     *
     * @param \DateTime $beginAt 起始時間
     * @param \DateTime $endAt   終止時間
     * @return Array
     */
     abstract protected function getEntryByTimeInterval($beginAt, $endAt);

    /**
     * 搜尋last_entry_at落在目標區間的使用者
     *
     * @param \DateTime $beginAt 起始時間
     * @param \DateTime $endAt   終止時間
     * @param integer   $startId 起始的搜尋ID
     * @return array
     */
     abstract protected function getHasEntryData($beginAt, $endAt, $startId);

    /**
     * 搜尋大於特定時間且有對應版號的最大版號明細
     *
     * @param \DateTime $targetTime 目標明細的時間
     * @param integer   $userId     使用者ID
     * @return array
     */
     abstract protected function getLastEntryByTime($targetTime, $userId);

    /**
     * 取得對應redis
     *
     * @param integer $userId 使用者ID
     * @return \Predis\Client
     */
    abstract protected function getRedis($userId);

    /**
     * 取得redis key
     *
     * @param integer $userId   使用者ID
     * @param integer $currency 幣別
     * @return string
     */
    abstract protected function getRedisKey($userId, $currency);
}
