<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;

class CheckRedisBalanceCommand extends ContainerAwareCommand
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
     * @var \Predis\Client
     */
    private $redis;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * 每次檢查的筆數
     *
     * @var integer
     */
    private $limit = 1000;

    /**
     * @var Boolean
     */
    private $delKey;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        if ($this->em) {
            return $this->em;
        }

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        return $this->em;
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('durian:check-redis-balance')
             ->setDescription('檢查redis餘額與資料庫是否同步')
             ->addOption('start-date', null, InputOption::VALUE_REQUIRED, '登入日期起', null)
             ->addOption('end-date', null, InputOption::VALUE_REQUIRED, '登入日期迄', null)
             ->addOption('payway', null, InputOption::VALUE_REQUIRED, '交易種類', null)
             ->addOption('del-key', null, InputOption::VALUE_NONE, '刪除redis key')
             ->setHelp(<<<EOT
檢查Cash、CashFake、Card、Credit存在redis餘額與資料庫是否同步
app/console durian:check-redis-balance --start-date="2013/01/01" --end-date="2013/01/31" --payway=cash
app/console durian:check-redis-balance --start-date="2013/01/01" --end-date="2013/01/31" --payway=cashfake
app/console durian:check-redis-balance --start-date="2013/01/01" --end-date="2013/01/31" --payway=credit
app/console durian:check-redis-balance --start-date="2013/01/01" --end-date="2013/01/31" --payway=card
EOT
             );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart('check-redis-balance');

        $startTime = microtime(true);
        $this->input  = $input;
        $this->output = $output;

        $this->setUpLogger();
        $this->delKey = $this->input->getOption('del-key');
        $payway = $this->input->getOption('payway');

        $startDate = new \DateTime($this->input->getOption('start-date'));
        $start = $startDate->format('Y-m-d H:i:s');
        $endDate = new \DateTime($this->input->getOption('end-date'));
        $end = $endDate->format('Y-m-d H:i:s');

        $count = $this->getAllUserNum($start, $end);

        $id = 0;
        $msgNum = 0;
        while ($count >= 0) {
            $users = $this->getIntervalUser($id, $start, $end);

            $ids = [];
            foreach ($users as $user) {
                $id = $user['id'];
                $ids[] = $user['id'];
            }

            //檢查Cash
            if ($payway == 'cash') {
                $msgNum += $this->checkCash($ids);
            }

            //檢查CashFake
            if ($payway == 'cashfake') {
                $msgNum += $this->checkCashFake($ids);
            }

            //檢查Card
            if ($payway == 'card') {
                $msgNum += $this->checkCard($ids);
            }

            //檢查Credit
            if ($payway == 'credit') {
                $msgNum += $this->checkCredit($ids);
            }

            unset($users);
            unset($ids);
            $this->getEntityManager()->clear();

            // 在跑測試的時候，就不sleep了，避免測試碼執行時間過長
            if ($this->getContainer()->getParameter('kernel.environment') != 'test') {
                usleep(500000);
            }

            $count -= $this->limit;
        }

        $endTime = microtime(true);
        $excutionTime = round($endTime - $startTime, 1);
        $timeString = $excutionTime . ' sec.';

        if ($excutionTime > 60) {
            $timeString = round($excutionTime / 60, 0) . ' mins.';
        }

        $output->write("\nExecute time: $timeString", true);

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage  = number_format($memory, 2);
        $output->write("Memory MAX use: $usage M", true);

        $bgMonitor->setMsgNum($msgNum);
        $bgMonitor->setLastEndTime($endDate);
        $bgMonitor->commandEnd();
    }

    /**
     * 檢查Cash
     *
     * @param array $userIds
     * @return integer
     */
    private function checkCash($userIds)
    {
        if (count($userIds) == 0) {
            return;
        }

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('c.currency, IDENTITY(c.user)as user, c.balance, c.preAdd, c.preSub');
        $qb->from('BBDurianBundle:Cash', 'c');
        $qb->where($qb->expr()->in('c.user', ':userIds'));
        $qb->setParameter('userIds', $userIds);

        $results = $qb->getQuery()->getResult();

        $count = 0;
        foreach ($results as $cash) {
            $cashKey = sprintf('cash_balance_%s_%s', $cash['user'], $cash['currency']);
            $this->redis = $this->getRedis($cash['user']);
            $hvals = $this->redis->hgetall($cashKey);

            if (!$hvals) {
                continue;
            }

            //等cash採用新交易機制後拔除
            if (isset($hvals['last_entry_at'])) {
                unset($hvals['last_entry_at']);
            }

            if (isset($hvals['version'])) {
                unset($hvals['version']);
            }

            $check = [
                'balance' => round($cash['balance'] * 10000),
                'pre_sub' => round($cash['preSub'] * 10000),
                'pre_add' => round($cash['preAdd'] * 10000)
            ];

            //餘額正確，刪除餘額快取
            if ($check == $hvals && $this->delKey) {
                $this->redis->del($cashKey);
            }

           //餘額不正確
            if ($check != $hvals) {
                $this->output->write("$cashKey 餘額不正確", true);
                $this->log("$cashKey 餘額不正確");
                $logStr = sprintf(
                    "balance : %s, pre_sub : %s, pre_add : %s different to %s balance : %s, pre_sub : %s, pre_add : %s",
                    $cash['balance'],
                    $cash['preSub'],
                    $cash['preAdd'],
                    $cashKey,
                    $hvals['balance'] / 10000,
                    $hvals['pre_sub'] / 10000,
                    $hvals['pre_add'] / 10000
                );
                $this->log($logStr);
            }

            $count++;
        }

        return $count;
    }

    /**
     * 檢查CashFake
     *
     * @param array $userIds
     * @return integer
     */
    private function checkCashFake($userIds)
    {
        if (count($userIds) == 0) {
            return;
        }

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('cf.currency, IDENTITY(cf.user)as user, cf.enable, cf.balance, cf.preAdd, cf.preSub, cf.version');
        $qb->from('BBDurianBundle:CashFake', 'cf');
        $qb->where($qb->expr()->in('cf.user', ':userIds'));
        $qb->setParameter('userIds', $userIds);

        $results = $qb->getQuery()->getResult();

        $count = 0;
        foreach ($results as $cashFake) {
            $cashFakeKey = sprintf('cash_fake_balance_%s_%s', $cashFake['user'], $cashFake['currency']);
            $this->redis = $this->getRedis($userIds[$count]);
            $hvals = $this->redis->hgetall($cashFakeKey);
            $redisVersion = "";
            $mysqlVersion = "";

            if (!$hvals) {
                continue;
            }

            $check = [
                'enable'  => $cashFake['enable'],
                'balance' => round($cashFake['balance'] * 10000),
                'pre_sub' => round($cashFake['preSub'] * 10000),
                'pre_add' => round($cashFake['preAdd'] * 10000)
            ];

            //若redis中cashFake有version才比對
            if(array_key_exists('version', $hvals)) {
                $check['version'] = $cashFake['version'];
                $redisVersion = ", version : " . $hvals['version'];
                $mysqlVersion = ", version : " . $cashFake['version'];
            }

            //餘額正確，刪除餘額快取
            if ($check == $hvals && $this->delKey) {
                $this->redis->del($cashFakeKey);
            }

            //餘額不正確
            if ($check != $hvals) {
                $this->log("$cashFakeKey 餘額不正確");
                $logStr = sprintf(
                    "enable : %s, balance : %s, pre_sub : %s, pre_add : %s" . $mysqlVersion . " different to %s enable : %s, balance : %s, pre_sub : %s, pre_add : %s" . $redisVersion,
                    $cashFake['enable'],
                    $cashFake['balance'],
                    $cashFake['preSub'],
                    $cashFake['preAdd'],
                    $cashFakeKey,
                    $hvals['enable'],
                    $hvals['balance'] / 10000,
                    $hvals['pre_sub'] / 10000,
                    $hvals['pre_add'] / 10000
                );
                $this->log($logStr);
                $this->output->write("$cashFakeKey 餘額不正確", true);
            }

            $count++;
        }

        return $count;
    }

    /**
     * 檢查Card
     *
     * @param array $userIds
     * @return integer
     */
    private function checkCard($userIds)
    {
        if (count($userIds) == 0) {
            return;
        }

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('IDENTITY(c.user)as user, c.balance, c.lastBalance, c.version');
        $qb->from('BBDurianBundle:Card', 'c');
        $qb->where($qb->expr()->in('c.user', ':userIds'));
        $qb->setParameter('userIds', $userIds);

        $results = $qb->getQuery()->getResult();

        $count = 0;
        foreach ($results as $card) {
            $cardKey = sprintf('card_balance_%s', $card['user']);
            $this->redis = $this->getRedis($card['user']);
            $hvals = $this->redis->hgetall($cardKey);
            $redisVersion = "";
            $mysqlVersion = "";

            if (!$hvals) {
                continue;
            }

            $check = [
                'balance'      => $card['balance'],
                'last_balance' => $card['lastBalance']
            ];

            //若redis中card有version才比對
            if(array_key_exists('version', $hvals)) {
                $check['version'] = $card['version'];
                $redisVersion = ", version : " . $hvals['version'];
                $mysqlVersion = ", version : " . $card['version'];
            }

            //餘額正確，刪除餘額快取
            if ($check == $hvals && $this->delKey) {
                $this->redis->del($cardKey);
            }

            //餘額不正確
            if ($check != $hvals) {
                $this->output->write("$cardKey 餘額不正確", true);
                $this->log("$cardKey 餘額不正確");
                $logStr = sprintf(
                    "balance : %s, last_balance : %s" . $mysqlVersion . " different to %s balance : %s, last_balance : %s" . $redisVersion,
                    $card['balance'],
                    $card['lastBalance'],
                    $cardKey,
                    $hvals['balance'],
                    $hvals['last_balance']
                );
                $this->log($logStr);
            }

            $count++;
        }

        return $count;
    }

    /**
     * 檢查Credit
     *
     * @param array $userIds
     * @return integer
     */
    private function checkCredit($userIds)
    {
        if (count($userIds) == 0) {
            return;
        }

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('IDENTITY(c.user)as user, c.groupNum, c.line, c.totalLine, c.version');
        $qb->from('BBDurianBundle:Credit', 'c');
        $qb->where($qb->expr()->in('c.user', ':userIds'));
        $qb->setParameter('userIds', $userIds);

        $results = $qb->getQuery()->getResult();

        $count = 0;
        foreach ($results as $credit) {
            $versionCorrect = true;
            $redisVersion = "";
            $mysqlVersion = "";

            $creditKey = sprintf(
                'credit_%s_%s',
                $credit['user'],
                $credit['groupNum']
            );

            $this->redis = $this->getRedis($userIds[$count]);
            $hvals = $this->redis->hgetall($creditKey);

            if (!$hvals) {
                continue;
            }

            $line = $credit['line'];
            $totalLine = $credit['totalLine'];

            //若redis中credit有version才比對
            if(array_key_exists('version', $hvals)) {
                $versionCorrect = $credit['version'] == $hvals['version'];
                $redisVersion = ", version : " . $hvals['version'];
                $mysqlVersion = ", version : " . $credit['version'];
            }

            $correct = ($line == $hvals['line'] && $totalLine == $hvals['total_line'] && $versionCorrect);

            //餘額正確，刪除餘額快取
            if ($correct && $this->delKey) {
                $this->redis->del($creditKey);
            }

            //餘額不正確
            if (!$correct) {
                $this->output->write("$creditKey 餘額不正確", true);
                $this->log("$creditKey 餘額不正確");
                $logStr = sprintf(
                    "line : %s, total_line : %s" . $mysqlVersion . " different to %s line : %s, total_line : %s" . $redisVersion,
                    $credit['line'],
                    $credit['totalLine'],
                    $creditKey,
                    $hvals['line'],
                    $hvals['total_line']
                );
                $this->log($logStr);
            }

            $count++;
        }

        return $count;
    }

    /**
     * 取得使用者總數
     *
     * @param string $startDate 查詢區間開始時間
     * @param string $endDate   查詢區間結束時間
     * @return integer
     */
    private function getAllUserNum($startDate, $endDate)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('count(u)');
        $qb->from('BBDurianBundle:User', 'u');

        if ($startDate) {
            $qb->andWhere('u.lastLogin >= :start')
               ->setParameter('start', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('u.lastLogin <= :end')
               ->setParameter('end', $endDate);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 傳回區間內所有的User
     *
     * @param integer $id        起始id
     * @param string  $startDate 查詢區間開始時間
     * @param string  $endDate   查詢區間結束時間
     * @return ArrayCollection
     */
    private function getIntervalUser($id, $startDate, $endDate)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('u.id');
        $qb->from('BBDurianBundle:User', 'u');
        $qb->where('u.id > :id');
        $qb->setParameter('id', $id);
        $qb->setMaxResults($this->limit);

        if ($startDate) {
            $qb->andWhere('u.lastLogin >= :start')
               ->setParameter('start', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('u.lastLogin <= :end')
               ->setParameter('end', $endDate);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳 Redis 操作物件
     *
     * @param string | integer $nameOrUserId Redis 名稱或使用者編號
     * @return \Predis\Client
     */
    private function getRedis($nameOrUserId = 'default')
    {
        // 皆需先強制轉為數字，以避免部分進入的 userId 為字串
        if ((int) $nameOrUserId) {
            if ($nameOrUserId % 4 == 0) {
                $nameOrUserId = 'wallet4';
            } elseif ($nameOrUserId % 4 == 3) {
                $nameOrUserId = 'wallet3';
            } elseif ($nameOrUserId % 4 == 2) {
                $nameOrUserId = 'wallet2';
            } elseif ($nameOrUserId % 4 == 1) {
                $nameOrUserId = 'wallet1';
            }
        }

        return $this->getContainer()->get("snc_redis.{$nameOrUserId}");
    }

    /**
     * 設定logger
     */
    private function setUpLogger()
    {
        $logger = $this->getContainer()->get('logger');

        $handler = $this->getContainer()->get('monolog.handler.check_redis_balance');
        $logger->pushHandler($handler);

        $this->logger = $logger;
    }

    /**
     * 記錄log
     *
     * @param String $message
     */
    private function log($msg)
    {
        if (null === $this->logger) {
            $this->setUpLogger();
        }

        $this->logger->addInfo($msg);
    }
}
