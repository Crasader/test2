<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 啟動CashEntryPoper物件來進行history cash entry queue的訊息處理
 * OPTION:revocer-fail，啟動處理失敗訊息的Poper。
 */
class SyncHisPoperCommand extends ContainerAwareCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:sync-his-poper')
            ->setDescription('Run durian sync history poper and consume message')
            ->addOption('recover-fail', null, InputOption::VALUE_NONE, 'Recover failed message from cash-entry-fail-queue')
            ->addArgument('site', InputArgument::OPTIONAL, '站別名稱, 此為假參數, 供sync history poper背景使用')
            ->addArgument('c', InputArgument::OPTIONAL, '此為假參數, 供判斷現在為第幾個poper');
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        $bgMonitor = $container->get('durian.monitor.background');
        $bgMonitor->commandStart('sync-his-poper');

        if ($input->getOption('recover-fail')) {
            $poper = new \BB\DurianBundle\Consumer\SyncHisRecoveryPoper();
        } else {
            $poper = new \BB\DurianBundle\Consumer\SyncHisPoper();
        }

        $msgNum = $poper->runPop($container, 'cash_entry');

        $bgMonitor->setMsgNum($msgNum);
        $bgMonitor->commandEnd();
    }
}
