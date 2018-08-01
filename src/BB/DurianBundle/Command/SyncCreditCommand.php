<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\CreditEntry;

/**
 * 用來同步信用額度、餘額、明細之物件
 */
class SyncCreditCommand extends ContainerAwareCommand
{
    /**
     * 在 Redis 會使用的 Keys
     *
     * @var array
     */
    protected $keys = [
        'creditQueue' => 'credit_queue',        // 等待同步之信用額度 (List) (每筆資料放 JSON)
        'periodQueue' => 'credit_period_queue', // 等待同步之累積金額佇列 (List) (每筆資料放 JSON)
        'entryQueue'  => 'credit_entry_queue',  // 交易明細佇列 (List) (每筆資料放 JSON)
    ];

    /**
     * 最多重試次數
     */
    const MAX_RETRY_TIMES = 10;

    /**
     * 用來判斷是否要修復失敗
     *
     * @var boolean
     */
    private $recoverFail;

    /**
     * 用來判斷是否處理過此筆資料
     *
     * @var array
     */
    private $done;

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
     * 輸出介面
     *
     * @var OutputInterface
     */
    private $output;

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
        return $this->getContainer()->get("snc_redis.default");
    }

    /**
     * 基本資訊設定
     */
    protected function configure()
    {
        $this->setName('durian:sync-credit')
            ->setDescription('同步信用額度的累積金額、交易明細')
            ->addOption('credit', null, InputOption::VALUE_NONE, '同步信用額度')
            ->addOption('entry', null, InputOption::VALUE_NONE, '同步交易明細')
            ->addOption('period', null, InputOption::VALUE_NONE, '同步累積金額(period)')
            ->addOption('recover-fail', null, InputOption::VALUE_NONE, '修復錯誤用')
            ->setHelp(<<<EOT
同步信用額度的累積金額、交易明細

在 Redis 中相關的 keys 如下:
(Credit)
    credit_queue        紀錄需要與資料庫同步之信用額度
    credit_queue_retry  等待重試
    credit_queue_failed 若前項重試失敗，會儲存於此 (--recover-fail)

(Entry)
    credit_entry_queue        紀錄需要與資料庫同步之交易明細
    credit_entry_queue_retry  等待重試
    credit_entry_queue_failed 若前項重試失敗，會儲存於此 (--recover-fail)

(Period)
    credit_period_queue        紀錄需要同步的累積金額資料(List)
    credit_period_queue_retry  等待重試
    credit_period_queue_failed 若前項重試失敗，會儲存於此 (--recover-fail)
EOT
        );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->recoverFail = false;

        if ($input->getOption('recover-fail')) {
            $this->recoverFail = true;
        }

        $this->lastHandleName = null;

        if ($input->getOption('credit')) {
            $this->syncCredit();
        }

        if ($input->getOption('entry')) {
            $this->syncEntry();
        }

        if ($input->getOption('period')) {
            $this->syncPeriod();
        }
    }

    /**
     * 同步信用額度資料
     */
    private function syncCredit()
    {
        $this->getBackgound()->commandStart('sync-credit');
        $this->setLogger('sync_credit');
        $this->sqlLogger->setEnable(true);

        $this->em = $this->getEntityManager();

        $this->done = [];

        $queueKey  = $this->keys['creditQueue'];
        $retryKey  = "{$queueKey}_retry";
        $failedKey = "{$queueKey}_failed";

        if ($this->recoverFail) {
            $count = $this->getCreditAndUpdate($failedKey, $failedKey);
        } else {
            $count = $this->getCreditAndUpdate($retryKey, $retryKey, $failedKey);
            $count += $this->getCreditAndUpdate($queueKey, $retryKey);
        }

        $this->sqlLogger->setEnable(false);
        $this->getBackgound()->setMsgNum($count);
        $this->getBackgound()->commandEnd();
    }

    /**
     * 同步交易明細資料
     */
    private function syncEntry()
    {
        $this->getBackgound()->commandStart('sync-credit-entry');
        $this->setLogger('sync_credit_entry');
        $this->sqlLogger->setEnable(true);

        $this->em = $this->getEntityManager();

        $this->done = [];

        $queueKey  = $this->keys['entryQueue'];
        $retryKey  = "{$queueKey}_retry";
        $failedKey = "{$queueKey}_failed";

        if ($this->recoverFail) {
            $count = $this->getEntryAndInsert($failedKey, $failedKey);
        } else {
            $count = $this->getEntryAndInsert($retryKey, $retryKey, $failedKey);
            $count += $this->getEntryAndInsert($queueKey, $retryKey);
        }

        $this->sqlLogger->setEnable(false);
        $this->getBackgound()->setMsgNum($count);
        $this->getBackgound()->commandEnd();
    }

    /**
     * 同步累積金額資料
     */
    private function syncPeriod()
    {
        $this->getBackgound()->commandStart('sync-credit-period');
        $this->setLogger('sync_credit');
        $this->sqlLogger->setEnable(true);

        $this->em = $this->getEntityManager();

        $this->done = [];

        $queueKey  = $this->keys['periodQueue'];
        $retryKey  = "{$queueKey}_retry";
        $failedKey = "{$queueKey}_failed";

        if ($this->recoverFail) {
            $count = $this->getPeriodAndUpdate($failedKey, $failedKey);
        } else {
            $count = $this->getPeriodAndUpdate($retryKey, $retryKey, $failedKey);
            $count += $this->getPeriodAndUpdate($queueKey, $retryKey);
        }

        $this->sqlLogger->setEnable(false);
        $this->getBackgound()->setMsgNum($count);
        $this->getBackgound()->commandEnd();
    }


    /**
     * 取出佇列中的信用額度，並更新資料
     *
     * @param string $queue       要處理的佇列
     * @param string $queueRetry  失敗時要放入的重試佇列
     * @param string $queueFailed 失敗過多時，要放入的失敗佇列
     *
     * @return integer
     */
    private function getCreditAndUpdate($queue, $queueRetry, $queueFailed = null)
    {
        $redis = $this->getRedis();

        $executeCount = 0;
        $userGroup = [];
        while (1) {
            if ($executeCount >= 1000) {
                break;
            }

            $credit = json_decode($redis->rpop($queue), true);
            if (!$credit) {
                break;
            }

            $userId = $credit['user_id'];
            $groupNum = $credit['group_num'];
            $version = $credit['version'];

            $index = sprintf('%s_%s', $userId, $groupNum);

            if (!isset($userGroup[$index])) {
                $userGroup[$index] = $credit;

                continue;
            }

            if ($userGroup[$index]['version'] < $version) {
                $userGroup[$index] = $credit;
            }

            $executeCount++;
        }

        $repo = $this->em->getRepository('BBDurianBundle:Credit');

        foreach ($userGroup as $credit) {
            try {
                $json = json_encode($credit);
                $repo->updateCreditData($credit);

                // 只刪除有重試過的資料
                if ($queue == $queueRetry) {
                    $redis->hdel($queueRetry . '_count', $json);
                }
            } catch (\Exception $e) {
                $exception = [
                    'time' => date('Y-m-d H:i:s'),
                    'result' => 'error',
                    'code' => $e->getCode(),
                    'message' => $e->getMessage()
                ];
                $this->output->writeln(print_r($exception, true));

                $this->pushToFailedOrNot($json, $queueRetry, $queueFailed);
            }
        }

        return $executeCount;
    }

    /**
     * 取出佇列中的累積金額，並更新資料
     *
     * @param string $queue       要處理的佇列
     * @param string $queueRetry  失敗時要放入的重試佇列
     * @param string $queueFailed 失敗過多時，要放入的失敗佇列
     *
     * @return integer
     */
    private function getPeriodAndUpdate($queue, $queueRetry, $queueFailed = null)
    {
        $redis = $this->getRedis();

        $executeCount = 0;
        $userGroupAt = [];
        while (1) {
            if ($executeCount >= 1000) {
                break;
            }

            $period = json_decode($redis->rpop($queue), true);
            if (!$period) {
                break;
            }

            $userId = $period['user_id'];
            $groupNum = $period['group_num'];
            $at = $period['at'];
            $version = $period['version'];

            $index = sprintf('%s_%s_%s', $userId, $groupNum, $at);

            if (!isset($userGroupAt[$index])) {
                $userGroupAt[$index] = $period;

                continue;
            }

            if ($userGroupAt[$index]['version'] < $version) {
                $userGroupAt[$index] = $period;
            }

            $executeCount++;
        }

        foreach ($userGroupAt as $period) {
            try {
                $json = json_encode($period);
                $this->updatePeriod($period);

                // 只刪除有重試過的資料
                if ($queue == $queueRetry) {
                    $redis->hdel($queueRetry . '_count', $json);
                }
            } catch (\Exception $e) {
                $exception = [
                    'time' => date('Y-m-d H:i:s'),
                    'result' => 'error',
                    'code' => $e->getCode(),
                    'message' => $e->getMessage()
                ];
                $this->output->writeln(print_r($exception, true));

                $this->pushToFailedOrNot($json, $queueRetry, $queueFailed);
            }
        }

        return $executeCount;
    }

    /**
     * 將佇列內的交易明細取出並新增至資料庫
     *
     * @param string $queue       要處理的佇列
     * @param string $queueRetry  失敗時要放入的重試佇列
     * @param string $queueFailed 失敗過多時，要放入的失敗佇列
     *
     * @return integer
     */
    private function getEntryAndInsert($queue, $queueRetry, $queueFailed = null)
    {
        $redis = $this->getRedis();
        $conn = $this->em->getConnection();

        $executeCount   = 0;
        $readyEntryData = [];
        $updateEntryAt  = [];

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

                $updateEntryAt[$entryData['user_id']] = $entryData['at'];
                $executeCount++;
            }

            foreach ($updateEntryAt as $userId => $at) {
                $sql = "UPDATE credit SET last_entry_at = $at WHERE user_id = $userId";
                $conn->executeUpdate($sql);
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

            $exception = [
                'time' => date('Y-m-d H:i:s'),
                'result' => 'error',
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ];
            $this->output->writeln(print_r($exception, true));

            foreach ($readyEntryData as $entryDataJson) {
                $this->pushToFailedOrNot($entryDataJson, $queueRetry, $queueFailed);
            }
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
     * 建立交易明細物件
     *
     * @param array $entryData 交易明細資料
     * @return Object
     */
    private function newEntry($entryData)
    {
        $entry = new CreditEntry(
            $entryData['user_id'],
            $entryData['group_num'],
            $entryData['opcode'],
            $entryData['amount'],
            $entryData['balance'],
            new \DateTime($entryData['period_at'])
        );

        $entry->setCreditId($entryData['credit_id']);
        $entry->setLine($entryData['line']);
        $entry->setTotalLine($entryData['total_line']);
        $entry->setAt($entryData['at']);
        $entry->setRefId($entryData['ref_id']);
        $entry->setMemo($entryData['memo']);
        $entry->setCreditVersion($entryData['credit_version']);

        return $entry;
    }

    /**
     * 更新累積金額
     *
     * @param array $periodInfo 使用者累積金額
     */
    private function updatePeriod($periodInfo)
    {
        $repo = $this->em->getRepository('BBDurianBundle:CreditPeriod');
        $params = [
            'userId' => $periodInfo['user_id'],
            'groupNum' => $periodInfo['group_num'],
            'at' => new \DateTime($periodInfo['at'])
        ];
        $period = $repo->findOneBy($params);

        // 不存在，要新增
        if (!$period) {
            $conn = $this->em->getConnection();
            $conn->insert('credit_period', $periodInfo);

            return;
        }

        // 存在，修改
        $repo->updatePeriodData($periodInfo);
    }
}
