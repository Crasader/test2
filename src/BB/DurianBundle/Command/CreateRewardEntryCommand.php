<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\Reward;
use BB\DurianBundle\Entity\RewardEntry;
use Monolog\Logger;

/**
 * 建立紅包明細
 *
 * @author Evan 2016.02.26
 */
class CreateRewardEntryCommand extends ContainerAwareCommand
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * 限制撈取筆數
     */
    private $limit = 1000;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $emShare;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:create-reward-entry')
            ->setDescription('產生紅包明細')
            ->setHelp(<<<EOT
產生搶紅包活動的明細
$ ./console durian:create-reward-entry
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->setUpLogger();

        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart('create-reward-entry');

        $this->emShare = $this->getEntityManager('share');

        $count = $this->createRewardEntry();

        $bgMonitor->setMsgNum($count);
        $bgMonitor->commandEnd();
        $this->emShare->getConnection()->close();
    }

    /**
     * 建立紅包明細
     *
     * @return integer
     */
    private function createRewardEntry()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.reward');
        $count = 0;

        try {
            // 抓最後一筆id
            $rewardId = $redis->lindex('reward_entry_created_queue', -1);

            if (!$rewardId) {
                return $count;
            }

            $reward = $redis->hgetall("reward_id_{$rewardId}");

            // 活動若被取消則不會有redis資訊，不用建明細，移除該活動id
            if (!$reward) {
                $redis->lrem('reward_entry_created_queue', -1, $rewardId);

                return $count;
            }

            // 建立之前已經有明細則刪除重新建立
            if ($this->hasEntry($rewardId)) {
                $this->deleteEntry($rewardId);
            }

            $quantity = $reward['quantity'];
            $min = $reward['min_amount'] * Reward::PLUS_NUMBER;
            $max = $reward['max_amount'] * Reward::PLUS_NUMBER;
            $amount = $reward['amount'] * Reward::PLUS_NUMBER;
            $remainAssign = $amount - $min * $quantity;
            $assignMax = $max - $min;

            // 先取出一包最大值的紅包，隨機產生最大值紅包放的位置
            $remainAssign -= $assignMax;
            $quantity--;
            $randomIndex = rand(1, $quantity);

            $this->emShare->beginTransaction();

            // 如果紅包只有一包則為最大值
            if ($quantity == 0) {
                $this->setEntry($rewardId, $max);
            }

            /**
             * 分配紅包方式
             * 全部紅包先給最小值，再加上剩餘可以分配的紅包金額
             */
            while ($quantity > 0) {
                $randomMin = 0;
                $count++;

                // 批次寫入mysql
                if ($count % $this->limit === 0) {
                    $this->emShare->flush();
                    $this->emShare->commit();
                    $this->emShare->clear();
                    $this->emShare->beginTransaction();
                }

                // 將一包最大值紅包放入對應位置
                if ($count == $randomIndex) {
                    $this->setEntry($rewardId, $max);

                    continue;
                }

                // 剩下的都用最小值分配
                if ($remainAssign == 0) {
                    $this->setEntry($rewardId, $min);
                    $quantity--;

                    continue;
                }

                /**
                 * 剩下的可以分配的金額比可分配的最大值小，表示分配此金額給紅包不會超過限制
                 * 將此金額分配給最後一包可分配的紅包，若還有剩餘的紅包則會用最小值分配
                 */
                if ($remainAssign < $assignMax) {
                    $assignAmount = $remainAssign + $min;
                    $this->setEntry($rewardId, $assignAmount);
                    $remainAssign = 0;
                    $quantity--;

                    continue;
                }

                // 全部紅包都用最大值與剩餘可分配金額的差距
                $range = $assignMax * $quantity - $remainAssign;

                /**
                 * 全部用最大值分配與可以分配的金額差距在可分配的最大值與最小值之間
                 * 設定亂數開頭，防止亂數太小造成紅包分配不完
                 */
                if (0 <= $range && $range < $assignMax) {
                    $randomMin = $assignMax - $range;
                }

                // 亂數產生金額
                $random = rand($randomMin, $assignMax);

                $assignAmount = $min + $random;
                $remainAssign = $remainAssign - $random;
                $this->setEntry($rewardId, $assignAmount);

                $quantity--;
            }

            // 設定明細已產生
            $dbReward = $this->emShare->find('BBDurianBundle:Reward', $rewardId);
            $dbReward->setEntryCreated();

            // 確保活動沒有在中途被刪除才更新redis
            if ($redis->exists("reward_id_{$rewardId}")) {
                $redis->hset("reward_id_{$rewardId}", 'entry_created', 1);
                $redis->sadd('reward_available', $rewardId);
            }

            // 設定ttl 時間
            $this->setTTL($reward);

            $this->emShare->flush();
            $this->emShare->commit();

            // 將已經建立好的活動從queue刪除
            $redis->lrem('reward_entry_created_queue', -1, $rewardId);

            $msg = "Reward $rewardId created entry successfully";
            $this->log($msg);
        } catch (\Exception $e) {
            if ($this->emShare->getConnection()->isTransactionActive()) {
                $this->emShare->rollback();
            }

            // 回復redis 的資料
            if ($redis->exists("reward_id_{$rewardId}")) {
                $redis->hset("reward_id_{$rewardId}", 'entry_created', 0);
                $redis->srem('reward_available', $rewardId);
            }

            // 送訊息至italking
            $italkingOperator = $container->get('durian.italking_operator');
            $exceptionType = get_class($e);
            $exceptionMsg = $e->getMessage();
            $server = gethostname();
            $now = date('Y-m-d H:i:s');

            $italkingOperator->pushExceptionToQueue(
                'developer_acc',
                $exceptionType,
                "[$server] [$now] 建立紅包明細，發生例外: $exceptionMsg"
            );

            $msg = "[WARNING]Created rewardList rewardId $rewardId failed, because $exceptionMsg";
            $this->log($msg);
        }

        $this->logger->popHandler()->close();

        return $count;
    }

    /**
     * 設定 redis 與 mysql 的明細
     *
     * @param integer $rewardId 活動編號
     * @param integer $amount 明細金額
     */
    private function setEntry($rewardId, $amount)
    {
        $redis = $this->getContainer()->get('snc_redis.reward');
        $amount /= Reward::PLUS_NUMBER;

        $seq = $this->generateSeq();

        $entry = new RewardEntry($rewardId, $amount);
        $entry->setId($seq);
        $this->emShare->persist($entry);

        $data = [
            'id' => $seq,
            'amount' => $amount
        ];

        // redis 用set 儲存，之後用spop 可以隨機取出明細
        $redis->sadd("reward_id_{$rewardId}_entry", json_encode($data));
    }

    /**
     * 產生紅包明細id
     *
     * @return integer
     */
    private function generateSeq()
    {
        $redisSeq = $this->getContainer()->get('snc_redis.sequence');
        $key = 'reward_seq';

        if (!$redisSeq->exists($key)) {
            throw new \RunTimeException('Cannot generate reward sequence id', 150760022);
        }

        return $redisSeq->incrby($key, 1);
    }

    /**
     * 設定紅包活動相關的TTL時間
     *
     * @param array $reward 活動資料
     */
    private function setTTL($reward)
    {
       $redis = $this->getContainer()->get('snc_redis.reward');

       // 活動參加過的使用者給初始值 -1，方便統一設定TTL
       $redis->sadd("reward_id_{$reward['id']}_attended_user", -1);

       $now = (new \DateTime('now'))->format(\DateTime::ISO8601);
       $diff = strtotime($reward['end_at']) - strtotime($now);
       $ttl = $diff + Reward::TTL_EXTEND;

       $redis->expire("reward_id_{$reward['id']}", $ttl);
       $redis->expire("reward_id_{$reward['id']}_entry", $ttl);
       $redis->expire("reward_id_{$reward['id']}_attended_user", $ttl);
    }

    /**
     * 檢查是否有已經建立的紅包明細
     *
     * @param integer $rewardId 紅包活動編號
     * @return boolean
     */
    private function hasEntry($rewardId)
    {
        $redis = $this->getContainer()->get('snc_redis.reward');
        $isRedisEntry = $redis->exists("reward_id_{$rewardId}_entry");

        if ($isRedisEntry) {
            return true;
        }

        $isDbEntry = $this->emShare->getRepository('BBDurianBundle:RewardEntry')
            ->findOneBy(['rewardId' => $rewardId]);

        if ($isDbEntry) {
            return true;
        }

        return false;
    }

    /**
     * 刪除已建立的活動明細
     *
     * @param integer $rewardId 紅包活動編號
     */
    private function deleteEntry($rewardId)
    {
        $conn = $this->emShare->getConnection();
        $redis = $this->getContainer()->get('snc_redis.reward');
        $count = 0;

        // 刪除redis 明細，一筆一筆pop
        while (true) {
            $entry = $redis->spop("reward_id_{$rewardId}_entry");
            $count++;

            if ($count % $this->limit === 0) {
                usleep(500000);
            }

            if (!$entry) {
                break;
            }
        }

        // 刪除資料庫明細
        $count = $this->emShare->getRepository('BBDurianBundle:RewardEntry')
            ->countListByRewardId(['reward_id' => $rewardId]);

        $sql = "DELETE FROM reward_entry WHERE reward_id = ? LIMIT $this->limit";

        // sqlite delete 不支援 limit
        if ($this->getContainer()->getParameter('kernel.environment') == 'test') {
            $sql = 'DELETE FROM reward_entry WHERE reward_id = ?';
        }

        $params = [$rewardId];
        $delRow = 0;

        while ($delRow < $count) {
            $delRow += $conn->executeUpdate($sql, $params);
            usleep(500000);
        }
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
     */
    private function setUpLogger()
    {
        $logger = $this->getContainer()->get('logger');
        $handler = $this->getContainer()->get('monolog.handler.create_reward_entry');
        $logger->pushHandler($handler);

        $this->logger = $logger;
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
