<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\Reward;
use Monolog\Logger;

/**
 * 同步抽中的紅包與派彩
 *
 * @author Evan 2016.04.06
 */
class ObtainRewardCommand extends ContainerAwareCommand
{
    /**
     * 搶紅包派彩opcode
     */
    const REWARD_BONUS_OPCODE = 1158;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * 紀錄最後一次採用的 Handler
     *
     * @var string
     */
    private $lastHandleName;

    /**
     * SQL Logger
     *
     * @var \BB\DurianBundle\Logger\SQL
     */
    private $sqlLogger;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:obtain-reward')
            ->setDescription('同步抽中紅包資訊、紅包派彩')
            ->addOption('sync', null, InputOption::VALUE_NONE, '同步抽中紅包資訊')
            ->addOption('do-op', null, InputOption::VALUE_NONE, '紅包派彩')
            ->setHelp(<<<EOT
同步抽中紅包資訊
$ ./console durian:obtain-reward --sync

紅包派彩
$ ./console durian:obtain-reward --do-op
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->lastHandleName = null;

        if ($input->getOption('sync')) {
            $this->syncObtainReward();
        }

        if ($input->getOption('do-op')) {
            $this->rewardOperation();
        }
    }

    /**
     * 同步抽中紅包資訊
     */
    private function syncObtainReward()
    {
        $this->getBackgound()->commandStart('sync-obtain-reward');
        $this->setUpLogger('sync_obtain_reward');

        $redis = $this->getContainer()->get('snc_redis.reward');
        $emShare = $this->getEntityManager('share');
        $config = $emShare->getConnection()->getConfiguration();
        $config->setSQLLogger($this->sqlLogger);

        $this->sqlLogger->setEnable(true);

        $count = 0;
        $syncData = [];

        $emShare->beginTransaction();
        try {
            while (true) {
                if ($count >= 1000) {
                    break;
                }

                $json = $redis->rpop('reward_sync_queue');
                $entryData = json_decode($json, true);

                if (!$entryData) {
                    break;
                }

                $count++;
                $syncData[] = $json;

                // 更新明細
                $entry = $emShare->find('BBDurianBundle:RewardEntry', $entryData['entry_id']);
                $entry->setUserId($entryData['user_id']);
                $entry->setObtainAt(new \DateTime($entryData['at']));

                // 更新紅包活動
                $reward = $emShare->find('BBDurianBundle:Reward', $entry->getRewardId());
                $reward->addObtainQuantity();
                $reward->addObtainAmount($entryData['amount']);
            }

            $emShare->flush();
            $emShare->commit();
        } catch(\Exception $e) {
            $emShare->rollback();

            // 推回redis
            foreach ($syncData as $json) {
                $redis->lpush('reward_sync_queue', $json);
            }

            // 送訊息至italking
            $italkingOperator = $this->getContainer()->get('durian.italking_operator');
            $exceptionType = get_class($e);
            $exceptionMsg = $e->getMessage();
            $server = gethostname();
            $now = date('Y-m-d H:i:s');

            $italkingOperator->pushExceptionToQueue(
                'developer_acc',
                $exceptionType,
                "[$server] [$now] 同步紅包明細, 發生例外: $exceptionMsg"
            );

            $msg = "[WARNING]Sync rewardEntry failed, because $exceptionMsg";
            $this->log($msg);
        }

        $this->sqlLogger->setEnable(false);
        $this->logger->popHandler()->close();
        $this->getBackgound()->setMsgNum($count);
        $this->getBackgound()->commandEnd();
    }

    /**
     * 紅包派彩
     */
    private function rewardOperation()
    {
        $this->getBackgound()->commandStart('op-obtain-reward');
        $this->setUpLogger('op_obtain_reward');

        $redis = $this->getContainer()->get('snc_redis.reward');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $opService = $this->getContainer()->get('durian.op');

        $count = 0;

        while (true) {
            try {
                $result = [];

                if ($count >= 1000) {
                    break;
                }

                $json = $redis->rpop('reward_op_queue');
                $data = json_decode($json, true);

                if (!$data) {
                    break;
                }

                $count++;
                $user = $em->find('BBDurianBundle:User', $data['user_id']);
                $entry = $emShare->find('BBDurianBundle:RewardEntry', $data['entry_id']);

                if (is_null($entry->getObtainAt())) {
                    throw new \RuntimeException('Reward entry not sync', 150760037);
                }

                if (!$user->getCash()) {
                    throw new \RuntimeException("User {$data['user_id']} has no cash");
                }

                $options = ['opcode' => self::REWARD_BONUS_OPCODE];

                $emShare->beginTransaction();
                $result = $opService->cashDirectOpByRedis($user->getCash(), $data['amount'], $options);
                $entry->setPayOffAt(new \Datetime());

                $emShare->flush();
                $emShare->commit();

                $msg = "Reward Entry {$entry->getId()} operation successfully";
                $this->log($msg);
            } catch(\Exception $e) {
                if ($emShare->getConnection()->isTransactionActive()) {
                    $emShare->rollback();
                }

                // 沒有派彩過就出錯需推回redis
                if (!$result && $json) {
                    $redis->lpush('reward_op_queue', $json);
                }

                // 尚未同步明細的訊息不用送到italking
                if ($e->getCode() != 150760037) {
                    // 送訊息至italking
                    $italkingOperator = $this->getContainer()->get('durian.italking_operator');
                    $exceptionType = get_class($e);
                    $exceptionMsg = $e->getMessage();
                    $server = gethostname();
                    $now = date('Y-m-d H:i:s');

                    $italkingOperator->pushExceptionToQueue(
                        'developer_acc',
                        $exceptionType,
                        "[$server] [$now] 紅包派彩 $json, 發生例外: $exceptionMsg"
                    );
                }

                $msg = "[WARNING]RewardEntry operation failed, data: $json, because {$e->getMessage()}";
                $this->log($msg);
            }
        }

        $this->logger->popHandler()->close();
        $this->getBackgound()->setMsgNum($count);
        $this->getBackgound()->commandEnd();
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
     * 回傳 EntityManager 物件
     *
     * @param string $name EntityManager名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 設定logger
     *
     * @param string $handleName 處理的logger名稱
     */
    private function setUpLogger($handleName)
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
     * 記錄log
     *
     * @param string $msg 訊息
     */
    private function log($msg)
    {
        $this->output->writeln($msg);
        $this->logger->addInfo($msg);
    }
}
