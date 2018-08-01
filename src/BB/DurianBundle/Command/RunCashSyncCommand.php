<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 啟動CashPoper物件來進行cash queue的訊息處理
 * OPTION:revocer-fail，啟動處理失敗訊息的Poper。
 */
class RunCashSyncCommand extends ContainerAwareCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:run-cash-sync')
            ->setDescription('Run durian cash sync poper and update mysql')
            ->addOption('recover-fail', null, InputOption::VALUE_NONE, 'Recover failed message from cash-fail-queue')
            ->addOption('executeQueue', null, InputOption::VALUE_OPTIONAL, '同步queue名稱')
            ->addArgument('site', InputArgument::OPTIONAL, '站別名稱, 此為假參數, 供cash poper背景使用')
            ->setHelp(<<<EOT
同步現金餘額

分配佇列
$ app/console durian:run-cash-sync

同步指定的佇列
$ app/console durian:run-cash-sync --executeQueue=0

同步指定的失敗佇列
$ app/console durian:run-cash-sync --executeQueue=0 --recover-fail
EOT
        );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $execQueue = null;
        $queue = $input->getOption('executeQueue');
        if (isset($queue)) {
            $execQueue = $queue;
        }

        $bgMonitor = $container->get('durian.monitor.background');
        $bgMonitor->commandStart('run-cash-sync');

        if ($input->getOption('recover-fail')) {
            if (!isset($execQueue)) {
                throw new \Exception('recover-fail can not without executeQueue');
            }

            $poper = new \BB\DurianBundle\Consumer\SyncRecoveryPoper();
        } else {
            $poper = new \BB\DurianBundle\Consumer\SyncPoper();
        }

        $msgNum = 0;

        if (!isset($execQueue)) {
            $msgNum = $poper->departQueue($container);
        } else {
            $msgNum = $poper->runPop($container, 'cash', $execQueue);
        }

        $bgMonitor->setMsgNum($msgNum);
        $bgMonitor->commandEnd();
    }
}
