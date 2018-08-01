<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 更新使用者下層數量
 */
class UpdateUserSizeCommand extends ContainerAwareCommand
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
     * 等待時間
     *
     * @var integer
     */
    private $waitTime;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('durian:update-user-size')
            ->setDescription('更新使用者下層數量')
            ->addOption('wait-time', null, InputOption::VALUE_OPTIONAL, '等待時間(單位毫秒)', null)
            ->setHelp(<<<EOT
更新 user size 欄位
app/console durian:update-user-size --wait-time=500000
EOT
             );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default');
        $bgMonitor = $container->get('durian.monitor.background');
        $bgMonitor->commandStart('update-user-size');

        $this->output = $output;
        $this->setOptions($input);
        $this->start();

        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:User');

        $msgNum = 0;
        $colQueue = [];
        $msgArray = [];

        $em->beginTransaction();
        try {
            while ($msgNum < 1000) {
                $queue = $redis->lpop('user_size_queue');
                $queueMsg = null;
                $queueMsg = json_decode($queue, true);

                if (empty($queueMsg)) {
                    break;
                }

                $msgNum++;

                if (!isset($colQueue[$queueMsg['index']])) {
                    $colQueue[$queueMsg['index']] = $queueMsg['value'];
                } else {
                    $colQueue[$queueMsg['index']] += $queueMsg['value'];
                }
            }

            foreach ($colQueue as $userId => $changeSize) {
                $repo->updateUserSize($userId, $changeSize);

                $now = (new \DateTime('now'))->format('Y-m-d H:i:s');
                $msg = "[$now] userId:$userId, size:$changeSize";
                $msgArray[] = $msg;
            }

            $em->flush();
            $em->commit();

            // 成功 commit 後才印出更新訊息
            foreach ($msgArray as $msg) {
                $this->output->writeln($msg);
            }

            if ($container->getParameter('kernel.environment') == 'test') {
                usleep($this->waitTime);
            }

            $bgMonitor->setMsgNum($msgNum);
            $bgMonitor->setLastEndTime(new \DateTime());
            $bgMonitor->commandEnd();
        } catch (\Exception $e) {
            if ($em->getConnection()->isTransactionActive()) {
                $em->rollback();
            }

            foreach ($colQueue as $userId => $changeSize) {
                if ($changeSize) {
                    $data = [
                        'index' => $userId,
                        'value' => $changeSize
                    ];
                    $redis->lpush('user_size_queue', json_encode($data));
                }
            }

            $now = (new \DateTime('now'))->format('Y-m-d H:i:s');

            $msg = " $now [WARNING]Update user size failed, because {$e->getMessage()}";
            $this->output->writeln($msg);
        }

        $this->end();
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
        $this->waitTime = 500000;

        if ($input->getOption('wait-time')) {
            $this->waitTime = $input->getOption('wait-time');
        }
    }
}
