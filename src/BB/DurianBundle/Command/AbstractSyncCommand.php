<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 用來同步餘額資料、交易明細、歷史資料庫、交易之物件
 */
abstract class AbstractSyncCommand extends ContainerAwareCommand
{
    /**
     * 最多重試次數
     */
    const MAX_RETRY_TIMES = 10;

    /**
     * @var \Predis\Client
     */
    private $redis;

    /**
     * 用來判斷是否要修復失敗
     *
     * @var boolean
     */
    private $recoverFail;

    /**
     * 用來判斷是否處理過此交易明細
     *
     * @var array
     */
    private $doneEntry;

    /**
     * 用來判斷是否處理過此餘額資訊
     *
     * @var array
     */
    private $doneBalance;

    /**
     * @see \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * 紀錄最後一次採用的 Handler
     *
     * @var string
     */
    private $lastHandleName;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * SQL Logger
     *
     * @var \BB\DurianBundle\Logger\SQL
     */
    private $sqlLogger;

    /**
     * 設定 Logger
     *
     * @param string $handleName Handler 名稱
     */
    private function setLogger($handleName)
    {
        $container = $this->getContainer();

        if (!$this->sqlLogger) {
            $this->sqlLogger = $container->get('durian.logger_sql');
        }

        if ($this->lastHandleName != $handleName) {
            $logger = $container->get('logger');
            $logger->popHandler();

            $handler = $container->get('monolog.handler.' . $handleName);
            $logger->pushHandler($handler);

            $this->logger = $logger;
            $this->lastHandleName = $handleName;
        }
    }

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name EntityManager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($name = "default")
    {
        $container = $this->getContainer();
        $em = $container->get("doctrine.orm.{$name}_entity_manager");

        $config = $em->getConnection()->getConfiguration();
        $config->setSQLLogger($this->sqlLogger);

        return $em;
    }

    /**
     * 回傳背景監控服務
     *
     * @return \BB\DurianBundle\Monitor\Background
     */
    private function getBackgound()
    {
        return $this->getContainer()->get('durian.monitor.background');
    }

    /**
     * 回傳 Redis 操作物件
     *
     * @return \Predis\Client
     */
    private function getRedis()
    {
        if (!isset($this->redis)) {
            $this->redis = $this->getContainer()->get("snc_redis.default");
        }

        return $this->redis;
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bg = $this->getContainer()->get('durian.monitor.background');

        $this->recoverFail = false;
        if ($input->getOption('recover-fail')) {
            $this->recoverFail = true;
        }

        $this->lastHandleName = null;

        // 同步交易明細
        if ($input->getOption('entry')) {
            $this->syncEntry();
        }

        // 同步餘額、預扣、預存
        if ($input->getOption('balance')) {
            $this->syncBalance();
        }

        // 同步歷史資料庫(僅交易明細)
        if (isset($this->keys['historyQueue']) && $input->getOption('history')) {
            $this->syncHistory();
        }

        // 同步交易(Transaction)
        if (isset($this->keys['transactionQueue']) && $input->getOption('transaction')) {
            $this->syncTransaction();
        }
    }

    /**
     * 同步交易明細
     */
    private function syncEntry()
    {
        $this->getBackgound()->commandStart($this->background['entry']);
        $this->setLogger($this->handlerSync);
        $this->sqlLogger->setEnable(true);

        $this->em = $this->getEntityManager();

        $this->doneEntry = [];

        $entQueueKey  = $this->keys['entryQueue'];
        $entRetryKey  = "{$entQueueKey}_retry";
        $entFailedKey = "{$entQueueKey}_failed";

        if ($this->recoverFail) {
            $count = $this->getEntryAndInsert($entFailedKey, $entFailedKey);
        } else {
            $count =  $this->getEntryAndInsert($entRetryKey, $entRetryKey, $entFailedKey);
            $count += $this->getEntryAndInsert($entQueueKey, $entRetryKey);
        }

        $this->sqlLogger->setEnable(false);
        $this->getBackgound()->setMsgNum($count);
        $this->getBackgound()->commandEnd();
    }

    /**
     * 同步餘額、預扣、預存
     */
    private function syncBalance()
    {
        $this->getBackgound()->commandStart($this->background['balance']);
        $this->setLogger($this->handlerSync);
        $this->sqlLogger->setEnable(true);

        $this->doneBalance = [];

        $this->em = $this->getEntityManager();

        $balanceQueueKey  = $this->keys['balanceQueue'];
        $balanceRetryKey  = "{$balanceQueueKey}_retry";
        $balanceFailedKey = "{$balanceQueueKey}_failed";

        if ($this->recoverFail) {
            $count = $this->getBalanceAndUpdate($balanceFailedKey, $balanceFailedKey);
        } else {
            $count =  $this->getBalanceAndUpdate($balanceRetryKey, $balanceRetryKey, $balanceFailedKey);
            $count += $this->getBalanceAndUpdate($balanceQueueKey, $balanceRetryKey);
        }

        $this->em->flush();
        $this->sqlLogger->setEnable(false);
        $this->getBackgound()->setMsgNum($count);
        $this->getBackgound()->commandEnd();
    }

    /**
     * 同步歷史資料庫(僅交易明細)
     */
    private function syncHistory()
    {
        $this->getBackgound()->commandStart($this->background['history']);
        $this->setLogger($this->handlerHistory);
        $this->sqlLogger->setEnable(true);

        $this->em = $this->getEntityManager('his');

        $this->doneEntry = [];

        $hisQueueKey  = $this->keys['historyQueue'];
        $hisRetryKey  = $hisQueueKey . '_retry';
        $hisFailedKey = $hisQueueKey . '_failed';

        if ($this->recoverFail) {
            $count = $this->getEntryAndInsert($hisFailedKey, $hisFailedKey);
        } else {
            $count =  $this->getEntryAndInsert($hisRetryKey, $hisRetryKey, $hisFailedKey);
            $count += $this->getEntryAndInsert($hisQueueKey, $hisRetryKey);
        }

        $this->em->flush();
        $this->sqlLogger->setEnable(false);
        $this->getBackgound()->setMsgNum($count);
        $this->getBackgound()->commandEnd();
    }

    /**
     * 同步交易(Transaction)
     */
    private function syncTransaction()
    {
        $this->getBackgound()->commandStart($this->background['transaction']);
        $this->setLogger($this->handlerSync);
        $this->sqlLogger->setEnable(true);

        $this->em = $this->getEntityManager();

        $transQueueKey  = $this->keys['transactionQueue'];
        $transRetryKey  = "{$transQueueKey}_retry";
        $transFailedKey = "{$transQueueKey}_failed";

        $updQueueKey  = $this->keys['transUpdateQueue'];
        $updRetryKey  = "{$updQueueKey}_retry";
        $updFailedKey = "{$updQueueKey}_failed";

        if ($this->recoverFail) {
            $count =  $this->getTransactionAndInsert($transFailedKey, $transFailedKey);
            $count += $this->getTransactionAndUpdate($updFailedKey, $updFailedKey);
        } else {
            $count =  $this->getTransactionAndInsert($transRetryKey, $transRetryKey, $transFailedKey);
            $count += $this->getTransactionAndInsert($transQueueKey, $transRetryKey);
            $count += $this->getTransactionAndUpdate($updRetryKey, $updRetryKey, $updFailedKey);
            $count += $this->getTransactionAndUpdate($updQueueKey, $updRetryKey);
        }

        $this->em->flush();
        $this->sqlLogger->setEnable(false);
        $this->getBackgound()->setMsgNum($count);
        $this->getBackgound()->commandEnd();
    }

    /**
     * 取出佇列中的交易制度編號，並更新餘額、預扣、預存
     *
     * @param string $queue       要處理的柱列
     * @param string $queueRetry  失敗時要放入的重試佇列
     * @param string $queueFailed 失敗過多時，要放入的失敗佇列
     *
     * @return integer
     */
    private function getBalanceAndUpdate($queue, $queueRetry, $queueFailed = null)
    {
        $redis = $this->getRedis();

        $executeCount = 0;
        $userBalance = [];
        while (1) {
            if ($executeCount >= 1000) {
                break;
            }

            $balanceInfo = json_decode($redis->rpop($queue), true);
            if (!$balanceInfo) {
                break;
            }

            $majorKey = $this->getBalanceQueueMajorKey();
            $keyId = $balanceInfo[$majorKey];
            $version = $balanceInfo['version'];

            if (!isset($userBalance[$keyId])) {
                $userBalance[$keyId] = $balanceInfo;
                continue;
            }

            if ($userBalance[$keyId]['version'] < $version) {
                $userBalance[$keyId] = array_merge($userBalance[$keyId], $balanceInfo);
            } else {
                $userBalance[$keyId] = $userBalance[$keyId] + $balanceInfo;
            }

            $executeCount++;
        }

        foreach ($userBalance as $keyId => $info) {
            try {
                $json = json_encode($info);
                $this->updateBalance($keyId, $info);

                // 只刪除有重試過的資料
                if ($queue == $queueRetry) {
                    $redis->hdel($queueRetry . '_count', $json);
                }
            } catch (\Exception $e) {
                // 如果跑測試時不顯示錯誤訊息
                if ($this->getContainer()->getParameter('kernel.environment') != 'test') {
                    $exMsg = [
                        date('Y-m-d H:i:s'),
                        'error',
                        $e->getCode(),
                        $e->getMessage()
                    ];

                    \Doctrine\Common\Util\Debug::dump($exMsg);
                }

                $this->pushToFailedOrNot($json, $queueRetry, $queueFailed);
            }
        }

        return $executeCount;
    }

    /**
     * 將佇列內的交易明細取出並新增至資料庫
     *
     * @param string $queue       要處理的柱列
     * @param string $queueRetry  失敗時要放入的重試佇列
     * @param string $queueFailed 失敗過多時，要放入的失敗佇列
     *
     * @return integer
     */
    private function getEntryAndInsert($queue, $queueRetry, $queueFailed = null)
    {
        $redis = $this->getRedis();

        $executeCount   = 0;
        $readyEntryData = [];

        $em = $this->em;

        $em->beginTransaction();

        try {
            while (1) {
                if ($executeCount >= 1000) {
                    break;
                }

                $entryDataJson = $redis->rpop($queue);
                $entryData = json_decode($entryDataJson, true);

                if (!$entryData) {
                    break;
                }

                $readyEntryData[] = $entryDataJson;

                $entry = $this->newEntry($entryData);
                $em->persist($entry);

                $executeCount++;
            }

            $em->flush();
            $em->commit();

            // 只刪除有重試過的資料
            if ($queue == $queueRetry) {
                foreach ($readyEntryData as $entryDataJson) {
                    $redis->hdel($queueRetry . '_count', $entryDataJson);
                }
            }
        } catch (\Exception $e) {
            $em->rollback();

            // 如果跑測試時不顯示錯誤訊息
            if ($this->getContainer()->getParameter('kernel.environment') != 'test') {
                $exMsg = [
                    date('Y-m-d H:i:s'),
                    'error',
                    $e->getCode(),
                    $e->getMessage()
                ];

                \Doctrine\Common\Util\Debug::dump($exMsg);
            }

            foreach ($readyEntryData as $entryDataJson) {
                $this->pushToFailedOrNot($entryDataJson, $queueRetry, $queueFailed);
            }
        }

        // 沒有定義歷史佇列，直接回傳結果
        if (!isset($this->keys['historyQueue'])) {
            return $executeCount;
        }

        // 如果是明細佇列，才需要放入歷史佇列
        if (strpos($queue, 'entry') !== false) {
            $hisQueueKey = $this->keys['historyQueue'];

            foreach ($readyEntryData as $entryDataJson) {
                $redis->lpush($hisQueueKey, $entryDataJson);
            }
        }

        return $executeCount;
    }

    /**
     * 將交易編號佇列內的交易取出並新增至資料庫
     *
     * @param string $queue       要處理的柱列
     * @param string $queueRetry  失敗時要放入的重試佇列
     * @param string $queueFailed 失敗過多時，要放入的失敗佇列
     *
     * @return integer
     */
    private function getTransactionAndInsert($queue, $queueRetry, $queueFailed = null)
    {
        $redis = $this->getRedis();

        $executeCount   = 0;
        $readyTransData = [];

        $em = $this->em;
        $em->beginTransaction();

        try {
            while (1) {
                if ($executeCount >= 1000) {
                    break;
                }

                $transDataJson = $redis->rpop($queue);
                $transData = json_decode($transDataJson, true);

                if (!$transData) {
                    break;
                }

                $readyTransData[] = $transDataJson;

                $trans = $this->newTransaction($transData);
                $em->persist($trans);

                $executeCount++;
            }

            $em->flush();
            $em->commit();

            // 只刪除有重試過的資料
            if ($queue == $queueRetry) {
                foreach ($readyTransData as $transDataJson) {
                    $redis->hdel($queueRetry . '_count', $transDataJson);
                }
            }

        } catch (\Exception $e) {
            $em->rollback();

            // 如果跑測試時不顯示錯誤訊息
            if ($this->getContainer()->getParameter('kernel.environment') != 'test') {
                $exMsg = [
                    date('Y-m-d H:i:s'),
                    'error',
                    $e->getCode(),
                    $e->getMessage()
                ];

                \Doctrine\Common\Util\Debug::dump($exMsg);
            }

            foreach ($readyTransData as $transDataJson) {
                $this->pushToFailedOrNot($transDataJson, $queueRetry, $queueFailed);
            }
        }

        return $executeCount;
    }

    /**
     * 將更新交易佇列內的交易取出並新增至資料庫
     *
     * @param string $queue       要處理的柱列
     * @param string $queueRetry  失敗時要放入的重試佇列
     * @param string $queueFailed 失敗過多時，要放入的失敗佇列
     *
     * @return integer
     */
    private function getTransactionAndUpdate($queue, $queueRetry, $queueFailed = null)
    {
        $redis = $this->getRedis();

        $executeCount   = 0;
        $limit = 1000;

        // 若為 retry queue，處理上限將改為 llen
        if (strpos($queue, 'retry') !== false) {
            $limit = $redis->llen($queue);
        }

        while (1) {
            if ($executeCount >= $limit) {
                break;
            }

            $json = $redis->rpop($queue);
            $transData = json_decode($json, true);

            if (!$transData) {
                break;
            }

            $transId = $transData['id'];

            try {
                $result = $this->updateTransaction($transId, $transData);

                // 如果執行失敗，queue 需回推
                if ($result == 0) {
                    $this->pushToFailedOrNot($json, $queueRetry, $queueFailed);
                }

                // 如果執行成功，且為 retry queue，則刪除已重試過的資料
                if ($result != 0 && $queue == $queueRetry) {
                    $redis->hdel($queueRetry . '_count', $json);
                }
            } catch (\Exception $e) {
                // 如果跑測試時不顯示錯誤訊息
                if ($this->getContainer()->getParameter('kernel.environment') != 'test') {
                    $exMsg = [
                        date('Y-m-d H:i:s'),
                        'error',
                        $e->getCode(),
                        $e->getMessage()
                    ];

                    \Doctrine\Common\Util\Debug::dump($exMsg);
                }

                $this->pushToFailedOrNot($json, $queueRetry, $queueFailed);
            }

            $executeCount++;
        }

        return $executeCount;
    }

    /**
     * 檢查重試佇列的重試次數
     * 若重試太多次則將資料放入失敗佇列，否則放入重試佇列
     *
     * @param string $data   資料
     * @param string $retry  重試佇列
     * @param string $failed 失敗佇列
     */
    private function pushToFailedOrNot($data, $retry, $failed)
    {
        $redis = $this->getRedis();

        if ($failed) {
            $times = $redis->hincrby($retry . '_count', $data, 1);

            if ($times >= self::MAX_RETRY_TIMES) {
                $redis->lpush($failed, $data);
                $redis->hdel($retry . '_count', $data);

                return;
            }
        }

        $redis->lpush($retry, $data);
        $redis->hsetnx($retry . '_count', $data, 1);
    }

    /**
     * 取得balanceQueue的主要key名稱
     *
     * @return string
     */
    abstract protected function getBalanceQueueMajorKey();

    /**
     * 建立交易明細物件
     *
     * @param Array $entryData 交易明細資料
     * @return Object
     */
    abstract protected function newEntry($entryData);

    /**
     * 更新餘額、預扣、預存
     *
     * $data 包括四個資料:
     *   $balance 餘額
     *   $preSub  預扣
     *   $preAdd  預存
     *   $version 版本號
     *
     * @param integer $userId 使用者編號
     * @param Array   $data   餘額資訊
     */
    abstract protected function updateBalance($userId, $data);

    /**
     * 建立交易物件
     *
     * @param Array $data 交易資料
     * @return CashTrans || CashFakeTrans
     */
    abstract protected function newTransaction($data);

    /**
     * 更新交易
     *
     * @param integer $transId 交易編號
     * @param Array   $data    交易資訊
     */
    abstract protected function updateTransaction($transId, $data);
}
