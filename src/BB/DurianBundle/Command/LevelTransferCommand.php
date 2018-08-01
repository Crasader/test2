<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 層級轉移
 */
class LevelTransferCommand extends ContainerAwareCommand
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * 暫存已轉移完成的最大userId的String Key
     *
     * @var string
     */
    private $finishedUserKey = 'transfer_finished_user';

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('durian:level-transfer')
            ->setDescription('層級轉移')
            ->setHelp(<<<EOT
會員層級轉移、更新層級人數
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart('level-transfer');

        // 處理會員層級轉移
        $transferCount = $this->processTransfer();

        // 有LevelTransfer資料時會設定logger，才需要pop
        if (!is_null($this->logger)) {
            $handler = $this->logger->popHandler();
            $handler->close();
        }

        // 紀錄轉移人數
        $bgMonitor->setMsgNum($transferCount);
        $bgMonitor->commandEnd();
    }

    /**
     * 處理會員層級轉移
     *
     * @return integer
     */
    private function processTransfer()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $this->em = $container->get('doctrine.orm.entity_manager');

        // 轉移人數
        $transferCount = 0;

        // 取出層級轉移表中建立時間最早的一筆資料
        $queue = json_decode($redis->lpop('level_transfer_queue'), true);

        if (!$queue) {
            return $transferCount;
        }

        $domain = $queue['domain'];
        $source = $queue['source'];
        $target = $queue['target'];

        $logContent = "Domain: {$domain}，來源層級ID: {$source}，目標層級ID: {$target}，開始轉移";
        $this->log($logContent);

        $process = $redis->hgetall('transfer_process_' . $queue['domain']);

        // 若同廳有其他正在轉移中的排程，則將條件 push 回 queue 最末端
        if ($process) {
            if ($process['source'] != $queue['source'] || $process['target'] != $queue['target']) {
                $redis->rpush('level_transfer_queue', json_encode($queue));

                $logContent = "Domain: {$domain}，來源層級ID: {$source}，目標層級ID: {$target}，同廳有其他正在轉移的層級";
                $this->log($logContent);

                return $transferCount;
            }
        }

        $redis->hmset(
            'transfer_process_' . $queue['domain'],
            [
                'source' => $queue['source'],
                'target' => $queue['target']
            ]
        );

        $this->finishedUserKey .= '_' . $queue['domain'];

        $levelTransfer = $this->em->getRepository('BBDurianBundle:LevelTransfer')
            ->findOneBy([
                'domain' => $queue['domain'],
                'source' => $queue['source'],
                'target' => $queue['target']
            ]);

        if (!$levelTransfer) {
            $redis->rpush('level_transfer_queue', json_encode($queue));

            $logContent = "Domain: {$domain}，來源層級ID: {$source}，目標層級ID: {$target}，資料庫轉移層級未寫入";
            $this->log($logContent);

            return $transferCount;
        }

        // 取出目標層級
        $target = $this->em->find('BBDurianBundle:Level', $levelTransfer->getTarget());

        // 取出已轉移完成的最大userId
        $finishedUser = $redis->get($this->finishedUserKey);

        // 若redis沒有資料則從0開始
        if (is_null($finishedUser)) {
            $finishedUser = 0;
        }

        // 找出符合目標層級條件的使用者
        $userIds = $this->getUserBy($levelTransfer->getSource(), $target, $finishedUser);
        $currencyUsers = [];

        $this->em->beginTransaction();
        try {
            // 若無符合條件的使用者，則轉移完成，開始更新人數
            if (empty($userIds)) {
                // 移除層級轉移資料
                $this->em->remove($levelTransfer);
                $redis->del($this->finishedUserKey);
                $redis->del('transfer_process_' . $queue['domain']);

                $finishedUser = 0;
            } else {
                // 轉移層級
                $transferCount = $this->em->getRepository('BBDurianBundle:UserLevel')
                    ->transferUserTo($userIds, $levelTransfer->getSource(), $levelTransfer->getTarget());

                $finishedUser = end($userIds);

                // 取得幣別統計人數
                $currencyUsers = $this->em->getRepository('BBDurianBundle:Cash')
                    ->getCurrencyUsersBy($userIds, $levelTransfer->getSource());

                $sum = 0;
                foreach ($currencyUsers as $value) {
                    $sum += $value;
                }

                if ($sum != $transferCount) {
                    throw new \RuntimeException('層級計數與幣別計數總和不一致');
                }

                // 更新層級幣別的人數
                $this->updateLevelCurrency($currencyUsers, $levelTransfer->getSource());
                $this->updateLevelCurrency($currencyUsers, $levelTransfer->getTarget(), true);

                // 取得來源層級及刷新目標層級，以便取得最新人數
                $source = $this->em->find('BBDurianBundle:Level', $levelTransfer->getSource());
                $this->em->refresh($target);

                // 更新層級目前人數
                $this->updateLevel($transferCount, $source);
                $this->updateLevel($transferCount, $target, true);
            }
            $this->em->flush();
            $this->em->commit();

            // 轉移成功才更新redis
            if ($finishedUser) {
                $redis->set($this->finishedUserKey, $finishedUser);
                $redis->lpush('level_transfer_queue', json_encode($queue));
            }
        } catch (\Exception $e) {
            $this->em->rollback();
            $redis->rpush('level_transfer_queue', json_encode($queue));

            // 發生例外，轉移人數歸0
            $transferCount = 0;

            $logContent = sprintf(
                'Domain: %d，來源層級ID: %d，目標層級ID: %d，轉移失敗。ErrorCode: %s，ErrorMsg: %s',
                $levelTransfer->getDomain(),
                $levelTransfer->getSource(),
                $levelTransfer->getTarget(),
                $e->getCode(),
                $e->getMessage()
            );
            $this->log($logContent);

            return $transferCount;
        }

        // 紀錄每次背景轉移的人數
        $logContent = sprintf(
            'Domain: %d，來源層級ID: %d，目標層級ID: %d，成功轉移 %d 個會員',
            $levelTransfer->getDomain(),
            $levelTransfer->getSource(),
            $levelTransfer->getTarget(),
            $transferCount
        );
        $this->log($logContent);

        foreach ($currencyUsers as $index => $value) {
            $logContent = sprintf(
                'Currency: %d，成功轉移 %d 個會員',
                $index,
                $value
            );
            $this->log($logContent);
        }

        return $transferCount;
    }

    /**
     * 回傳符合目標層級條件的使用者
     *
     * @param integer $sourceId 來源層級Id
     * @param \BB\DurianBundle\Entity\Level $target 目標層級
     * @param integer $finishedUser 已轉移完成的最大userId
     * @return array
     */
    private function getUserBy($sourceId, $target, $finishedUser)
    {
        // 目標層級條件
        $transferCriteria = [
            'levelId' => $sourceId,
            'locked' => 0,
            'finishedUser' => $finishedUser
        ];

        if ($target->getCreatedAtStart()) {
            $transferCriteria['startAt'] = $target->getCreatedAtStart()->format('Y-m-d H:i:s');
        }

        if ($target->getCreatedAtEnd()) {
            $transferCriteria['endAt'] = $target->getCreatedAtEnd()->format('Y-m-d H:i:s');
        }

        if ($target->getDepositCount() != 0) {
            $transferCriteria['depositCount'] = $target->getDepositCount();
        }

        if ($target->getDepositTotal() != 0) {
            $transferCriteria['depositTotal'] = $target->getDepositTotal();
        }

        if ($target->getDepositMax() != 0) {
            $transferCriteria['depositMax'] = $target->getDepositMax();
        }

        if ($target->getWithdrawCount() != 0) {
            $transferCriteria['withdrawCount'] = $target->getWithdrawCount();
        }

        if ($target->getWithdrawTotal() != 0) {
            $transferCriteria['withdrawTotal'] = $target->getWithdrawTotal();
        }

        // 可轉移的使用者Id
        $transferUsers = [];

        // 檢查是否符合目標層級條件
        $userStats = $this->em->getRepository('BBDurianBundle:UserStat')
            ->getLevelTransferUser($transferCriteria, 0, 1000);

        foreach ($userStats as $stat) {
            $transferUsers[] = $stat['id'];
        }

        return $transferUsers;
    }

    /**
     * 更新層級人數
     *
     * @param integer $transferCount 轉移人數
     * @param \BB\DurianBundle\Entity\Level $level 層級
     * @param boolean $isAdd 是否人數相加
     */
    private function updateLevel($transferCount, $level, $isAdd = false)
    {
        $redis = $this->getContainer()->get('snc_redis.default');

        try {
            $data = [
                'index' => $level->getId(),
                'value' => $transferCount
            ];

            if (!$isAdd) {
                $data['value'] *= (-1);
            }

            $redis->rpush('level_user_count_queue', json_encode($data));
        } catch (\Exception $e) {
            // 將例外訊息加上發生例外時的動作
            $msg = sprintf(
                "更新層級人數時發生異常，%s",
                $e->getMessage()
            );

            throw new \Exception($msg, $e->getCode());
        }
    }

    /**
     * 更新層級幣別的人數
     *
     * @param array $currencyUsers 幣別的統計人數
     * @param integer $levelId 層級ID
     * @param boolean $isAdd 是否人數相加
     */
    private function updateLevelCurrency($currencyUsers, $levelId, $isAdd = false)
    {
        $redis = $this->getContainer()->get('snc_redis.default');
        $levelCurrencyRepo = $this->em->getRepository('BBDurianBundle:LevelCurrency');
        $currencySet = array_keys($currencyUsers);

        $criteria = [
            'levelId' => $levelId,
            'currency' => $currencySet
        ];

        $levelCurrencys = $levelCurrencyRepo->findBy($criteria);

        try {
            // 修改幣別人數
            foreach ($levelCurrencys as $levelCurrency) {
                $currency = $levelCurrency->getCurrency();

                $data = [
                    'index' => $levelCurrency->getLevelId() . '_' . $currency,
                    'value' => $currencyUsers[$currency]
                ];

                if (!$isAdd) {
                    $data['value'] *= (-1);
                }

                $redis->rpush('level_currency_user_count_queue', json_encode($data));
            }
        } catch (\Exception $e) {
            // 將例外訊息加上發生例外時的動作
            $msg = sprintf(
                "更新層級幣別人數時發生異常，%s",
                $e->getMessage()
            );

            throw new \Exception($msg, $e->getCode());
        }
    }

    /**
     * 設定logger
     */
    private function setUpLogger()
    {
        $this->logger = $this->getContainer()->get('durian.logger_manager')
            ->setUpLogger('level_transfer.log');
    }

    /**
     * 記錄log
     *
     * @param string $msg
     */
    private function log($msg)
    {
        if (is_null($this->logger)) {
            $this->setUpLogger();
        }

        $this->logger->addInfo($msg);
    }
}
