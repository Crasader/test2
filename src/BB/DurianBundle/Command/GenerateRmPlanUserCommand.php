<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Monolog\Logger;

/**
 * 產生準備要刪除的使用者佇列
 */
class GenerateRmPlanUserCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * 程式開始執行時間
     *
     * @var \DateTime
     */
    private $startTime;

    /**
     * 每次下語法刪掉的筆數
     *
     * @var integer
     */
    private $batchSize;

    /**
     * 等待時間
     *
     * @var integer
     */
    private $waitTime;

    /**
     * 是否為測試環境
     *
     * @var boolean
     */
    private $isTest;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $emShare;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('durian:generate-rm-plan-user')
            ->setDescription('產生準備要刪除的使用者佇列')
            ->addOption('batch-size', null, InputOption::VALUE_OPTIONAL, '批次處理的數量', null)
            ->addOption('wait-time', null, InputOption::VALUE_OPTIONAL, '等待時間(單位毫秒)', null)
            ->setHelp(<<<EOT
產生準備要刪除的使用者佇列
app/console durian:generate-rm-plan-user --batch-size=10000 --wait-time=500000
EOT
             );
    }

    /**
     * 開始執行、紀錄開始時間
     */
    private function start()
    {
        $this->startTime = new \DateTime;
    }

    /**
     * 程式結束顯示處理時間、記憶體
     */
    private function end()
    {
        $endTime = new \DateTime;
        $costTime = $endTime->diff($this->startTime, true);
        $this->output->writeln('Execute time: ' . $costTime->format('%H:%I:%S'));

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);
        $this->output->writeln("Memory MAX use: $usage M");
    }

    /**
     * 設定參數
     *
     * @param InputInterface $input
     */
    private function setOptions(InputInterface $input)
    {
        $this->batchSize = 10000;

        if ($input->getOption('batch-size')) {
            $this->batchSize = $input->getOption('batch-size');
        }

        $this->waitTime = 500000;

        if ($input->getOption('wait-time')) {
            $this->waitTime = $input->getOption('wait-time');
        }

        $this->isTest = false;

        if ($this->getContainer()->getParameter('kernel.environment') == 'test') {
            $this->isTest = true;
        }
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart('generate-rm-plan-user');

        $this->output = $output;
        $this->setOptions($input);
        $this->setUpLogger();
        $this->start();

        $msgNum = 0;
        $this->em = $this->getEntityManager();
        $this->emShare = $this->getEntityManager('share');
        $planQueue = $this->emShare->getRepository('BBDurianBundle:RmPlanQueue')
            ->findBy([], [], 10);

        foreach ($planQueue as $queue) {
            $msgNum += $this->generate($queue->getPlanId());
            $this->emShare->remove($queue);

            // 測試時不暫停
            if (!$this->isTest) {
                usleep($this->waitTime);
            }
        }

        $this->emShare->flush();

        $this->end();
        $bgMonitor->setMsgNum($msgNum);
        $bgMonitor->setLastEndTime(new \DateTime);
        $bgMonitor->commandEnd();
    }

    /**
     * 產生使用者佇列
     *
     * @param integer $planId 計畫編號
     * @return integer
     */
    private function generate($planId)
    {
        $plan = $this->emShare->find('BBDurianBundle:RmPlan', $planId);
        if (!$plan) {
            return 0;
        }

        $parent = $this->em->find('BBDurianBundle:User', $plan->getParentId());
        if (!$parent) {
            return 0;
        }

        $level = [];
        $rpLevelRepo = $this->emShare->getRepository('BBDurianBundle:RmPlanLevel');
        $rpLevels = $rpLevelRepo->findBy(['planId' => $planId]);

        foreach ($rpLevels as $rpLevel) {
            $level[] = $rpLevel->getLevelId();
        }

        $depth = $plan->getDepth();
        $lastLogin = $plan->getLastLogin();
        $userCreatedAt = $plan->getUserCreatedAt();
        $loginBefore = new \DateTime('now');
        $loginBefore->sub(new \DateInterval('P60D'));
        $payway = null;

        if ($lastLogin) {
            $parameters = [
                'level' => $level,
                'depth' => $depth,
                'last_login' => $lastLogin,
                'order_by' => ['id' => 'asc']
            ];
        }

        // 刪除指定的使用者建立時間之前，距今超過兩個月未登入，沒有出入款紀錄的現金帳號或沒有api轉入轉出的假現金帳號
        // BBIN定期刪除計畫不會使用層級當條件
        if ($userCreatedAt) {
            $payway = 'both';
            $userPayway = $this->em->find('BBDurianBundle:UserPayway', $parent->getId());

            if ($userPayway->isCashEnabled() && !$userPayway->isCashFakeEnabled()) {
                $payway = 'cash';
            }

            if (!$userPayway->isCashEnabled() && $userPayway->isCashFakeEnabled()) {
                $payway = 'cashFake';
            }

            $parameters = [
                'depth' => $depth,
                'created_at' => $userCreatedAt,
                'login_before' => $loginBefore,
                'order_by' => ['id' => 'asc']
            ];
        }

        $userCount = 0;
        if ($payway == 'both' && $userCreatedAt) {
            $parameters['payway'] = 'cash';
            $userCount = $this->findUserAndPushQueue($planId, $parent, $parameters);
            $parameters['payway'] = 'cashFake';
            $userCount += $this->findUserAndPushQueue($planId, $parent, $parameters);
        } else {
            $parameters['payway'] = $payway;
            $userCount = $this->findUserAndPushQueue($planId, $parent, $parameters);
        }

        $plan->queueDone();

        if (!$userCount) {
            $plan->confirm();
            $plan->finish();
            $plan->setMemo('沒有建立任何待刪除使用者');
        }

        $this->emShare->flush();

        return $userCount;
    }

    /**
     * 撈出符合條件的使用者並推進queue
     *
     * @param integer $planId 計畫編號
     * @param User $parent 上層
     * @param array $parameters 參數集合
     * @return int
     */
    private function findUserAndPushQueue($planId, $parent, $parameters)
    {
        $redis = $this->getContainer()->get("snc_redis.default");

        $num = 0;
        $firstResult = 0;
        $repo = $this->em->getRepository('BBDurianBundle:User');

        while (true) {
            $parameters['first_result'] = $firstResult;
            $parameters['max_results'] = $this->batchSize;

            $users = $repo->findChildByTime($parent, $parameters);
            $queue = [];
            $count = 0;
            foreach ($users as $user) {
                $msg = [
                    'plan_id' => $planId,
                    'user_id' => $user['id']
                ];

                $queue[] = json_encode($msg);
                $count++;
            }

            if ($count) {
                $redis->hincrby("rm_plan_$planId", 'count', $count);
                $redis->lpush('rm_plan_user_queue', $queue);

                foreach ($users as $user) {
                    $msg = "User {$user['id']} generate RmPlanUser successfully";
                    $this->log($msg);
                }
            }

            $num += $count;

            $firstResult += $this->batchSize;

            if (!$users) {
                break;
            }
        }

        return $num;
    }

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name EntityManager 名稱
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
        $handler = $this->getContainer()->get('monolog.handler.generate_rm_plan_user');
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
