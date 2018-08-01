<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 監控Queue長度，若長度超過上限送Italking(目前只有CashEntry)
 *
 * @author Devin 2017.04.11
 */
class MonitorQueueLengthCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * Queue上限長度對應表，長度超過此值送Italking
     *
     * @var array
     */
    private $limitLen = [
        'cash_queue' => 80000
    ];

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:monitor-queue-length')
            ->setDescription('監控Queue長度，若長度超過上限送Italking(目前只監控CashEntry)')
            ->setHelp(<<<EOT
監控Queue長度
$ ./console durian:monitor-queue-length
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart('monitor-queue-length');

        $this->monitorQueueLength();

        $bgMonitor->commandEnd();
    }

    /**
     * 檢查Queue的長度
     */
    private function monitorQueueLength()
    {
        $this->setUpLogger();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $queueLen = $redis->llen('cash_queue');

        if ($queueLen >= $this->limitLen['cash_queue']) {
            $italkingOperator = $this->getContainer()->get('durian.italking_operator');

            $now = date('Y-m-d H:i:s');
            $msg = "[$now] 現金明細同步至資料庫有堆積情形，請通知 RD5-帳號研發部值班人員檢查。";

            $italkingOperator->pushMessageToQueue('acc_system', $msg);

            $this->log($msg);
        }

        $this->logger->popHandler()->close();
    }

    /**
     * 設定logger
     */
    private function setUpLogger()
    {
        $logger = $this->getContainer()->get('logger');
        $handler = $this->getContainer()->get('monolog.handler.monitor_queue_length');

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
