<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\UserHasDepositWithdraw;

/**
 * 同步使用者現金存提款紀錄
 */
class SyncUserDepositWithdrawCommand extends ContainerAwareCommand
{
    /**
     * 最多重試次數
     */
    const MAX_RETRY_TIMES = 10;

    /**
     * SQL Logger
     *
     * @var \BB\DurianBundle\Logger\SQL
     */
    private $sqlLogger;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \Predis\Client
     */
    protected $redis;

    /**
     * 輸出介面
     *
     * @var OutputInterface
     */
    private $output;

    /**
     * 使用者存提款紀錄
     *
     * @var array
     */
    protected $records;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:sync-user-deposit-withdraw')
            ->setDescription('同步使用者現金存提款紀錄')
            ->addOption('recover-fail', null, InputOption::VALUE_NONE, '修復錯誤')
            ->setHelp(<<<EOT
同步使用者存提款紀錄
app/console durian:sync-user-deposit-withdraw
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart('sync-user-deposit-withdraw');

        $this->output = $output;
        $this->redis = $this->getRedis();
        $this->em = $this->getEntityManager();
        $this->setLogger();
        $this->sqlLogger->setEnable(true);

        $queue = 'cash_deposit_withdraw_queue';
        $retryQueue = 'cash_deposit_withdraw_retry_queue';
        $failedQueue = 'cash_deposit_withdraw_failed_queue';

        $msgNum = 0;
        if ($input->getOption('recover-fail')) {
            $msgNum += $this->processQueue($failedQueue, true);
        } else {
            $msgNum += $this->processQueue($retryQueue, true);
            $msgNum += $this->processQueue($queue);
        }

        if ($this->records) {
            $this->updateOrInsertRecord();
        }

        $this->sqlLogger->setEnable(false);

        $bgMonitor->setMsgNum($msgNum);
        $bgMonitor->commandEnd();
    }

    /**
     * 處理使用者存提款紀錄佇列
     *
     * @param string  $queueName 佇列名稱
     * @param boolean $retry     是否為重試
     * @return integer
     */
    private function processQueue($queueName, $retry = false)
    {
        $count = 0;

        while ($count <= 1000) {
            try {
                $queueMsg = [];
                $queueMsg = json_decode($this->redis->rpop($queueName), true);

                if (!$queueMsg) {
                    break;
                }

                $user = $this->em->find('BBDurianBundle:User', $queueMsg['user_id']);

                if (!$user) {
                    continue;
                }

                if (!$retry && $user->getRole() != 1) {
                    continue;
                }

                $this->collectRecord($queueMsg, true);

                // 因重試與失敗佇列裡的資料有可能為代理，確保是會員才紀錄上層
                if ($user->getRole() == 1) {
                    $parentId = $user->getParent()->getId();
                    $queueMsg['user_id'] = $parentId;

                    $this->collectRecord($queueMsg);
                }

                $count++;
            } catch (\Exception $e) {
                $exception = [
                    'time'    => date('Y-m-d H:i:s'),
                    'result'  => 'error',
                    'code'    => $e->getCode(),
                    'message' => $e->getMessage()
                ];
                $this->output->writeln(print_r($exception, true));

                // 確保$queueMsg有值才重推
                if ($queueMsg) {
                    $this->pushToRetryFailed($queueMsg);
                }
            }
        }

        return $count;
    }

    /**
     * 蒐集使用者存提款資料
     *
     * @param object $queueMsg 佇列訊息
     * @param boolean $updateFirstDepositAt 是否需update首次入款時間
     */
    private function collectRecord($queueMsg, $updateFirstDepositAt = false)
    {
        $errCount = $queueMsg['ERRCOUNT'];
        $userId = $queueMsg['user_id'];

        if (!isset($this->records[$userId])) {
            $this->records[$userId] = [
                'ERRCOUNT' => $errCount,
                'user_id' => $userId,
                'deposit' => false,
                'withdraw' => false,
                'update_first_deposit_at' => $updateFirstDepositAt,
            ];
        }

        if (array_key_exists('deposit_at', $queueMsg)) {
            $this->records[$userId]['deposit'] = true;
            $this->records[$userId]['deposit_at'] = new \DateTime($queueMsg['deposit_at']);
        }

        if (array_key_exists('withdraw_at', $queueMsg)) {
            $this->records[$userId]['withdraw'] = true;
            $this->records[$userId]['withdraw_at'] = new \DateTime($queueMsg['withdraw_at']);
        }
    }

    /**
     * 更新或新增使用者存提款紀錄
     */
    private function updateOrInsertRecord()
    {
        foreach ($this->records as $record) {
            try {
                $userId = $record['user_id'];
                $deposit = $record['deposit'];
                $withdraw = $record['withdraw'];
                $depositAt = null;
                $withdrawAt = null;
                $updateFirstDepositAt = $record['update_first_deposit_at'];

                if (array_key_exists('deposit_at', $record)) {
                    $depositAt = $record['deposit_at'];
                }

                if (array_key_exists('withdraw_at', $record)) {
                    $withdrawAt = $record['withdraw_at'];
                }

                $depositWithdraw = $this->em->find('BBDurianBundle:UserHasDepositWithdraw', $userId);

                if (!$depositWithdraw) {
                    $user = $this->em->find('BBDurianBundle:User', $userId);
                    $depositWithdraw = new UserHasDepositWithdraw($user, $depositAt, $withdrawAt, $deposit, $withdraw);

                    if ($depositAt && $updateFirstDepositAt) {
                        $depositWithdraw->setFirstDepositAt($depositAt->format('YmdHis'));
                    }

                    $this->em->persist($depositWithdraw);
                    $this->em->flush();

                    continue;
                }

                if ($deposit) {
                    if (!$depositWithdraw->isDeposited()) {
                        $depositWithdraw->setDeposit(true);
                    }

                    $depositWithdraw->setDepositAt($depositAt);

                    if ($updateFirstDepositAt) {
                        // 如果首次入款時間為null或首次入款時間>入款時間, 則將入款時間設為首次入款時間
                        $firstDepositAt = $depositWithdraw->getFirstDepositAt();
                        if (!$firstDepositAt || $firstDepositAt > $depositAt) {
                            $depositWithdraw->setFirstDepositAt($depositAt->format('YmdHis'));
                        }
                    }
                }

                if ($withdraw) {
                    if (!$depositWithdraw->isWithdrew()) {
                        $depositWithdraw->setWithdraw(true);
                    }

                    $depositWithdraw->setWithdrawAt($withdrawAt);
                }

                $this->em->flush();
            } catch (\Exception $e) {
                $exception = [
                    'time'    => date('Y-m-d H:i:s'),
                    'result'  => 'error',
                    'code'    => $e->getCode(),
                    'message' => $e->getMessage()
                ];
                $this->output->writeln(print_r($exception, true));

                if (array_key_exists('withdraw_at', $record)) {
                    $record['withdraw_at'] = $record['withdraw_at']->format('Y-m-d H:i:s');
                }

                if (array_key_exists('deposit_at', $record)) {
                    $record['deposit_at'] = $record['deposit_at']->format('Y-m-d H:i:s');
                }

                $this->pushToRetryFailed($record);
            }
        }
    }

    /**
     * 將失敗的紀錄推入重試或失敗佇列
     *
     * @param array $record 重試或失敗的資料
     */
    private function pushToRetryFailed($record)
    {
        $retryQueue = 'cash_deposit_withdraw_retry_queue';
        $failedQueue = 'cash_deposit_withdraw_failed_queue';

        $record['ERRCOUNT']++;
        if ($record['ERRCOUNT'] >= self::MAX_RETRY_TIMES) {
            $this->redis->lpush($failedQueue, json_encode($record));

            return;
        }

        $this->redis->lpush($retryQueue, json_encode($record));
    }

    /**
     * 回傳 Redis 操作物件
     *
     * @return \Predis\Client
     */
    private function getRedis()
    {
        return $this->getContainer()->get('snc_redis.default_client');
    }

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name EntityManager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        $container = $this->getContainer();
        $em = $container->get("doctrine.orm.{$name}_entity_manager");

        return $em;
    }

    /**
     * 設定 Logger
     */
    private function setLogger()
    {
        $container = $this->getContainer();

        $logger = $container->get('logger');
        $handler = $container->get('monolog.handler.sync_user_deposit_withdraw');
        $logger->pushHandler($handler);

        $this->logger = $logger;
        $this->sqlLogger = $container->get('durian.logger_sql');

        $config = $this->em->getConnection()->getConfiguration();
        $config->setSQLLogger($this->sqlLogger);
    }
}
