<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 啟動CardPoper物件來進行card queue的訊息處理
 * OPTION:revocer-fail，啟動處理失敗訊息的Poper。
 */
class RunCardPoperCommand extends ContainerAwareCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:run-card-poper')
            ->setDescription('Run durian card poper and consume message')
            ->addOption('recover-fail', null, InputOption::VALUE_NONE, 'Recover failed message from card-fail-queue')
            ->addArgument('site', InputArgument::OPTIONAL, '站別名稱, 此為假參數, 供card poper背景使用')
            ->addArgument('c', InputArgument::OPTIONAL, '此為假參數, 供判斷現在為第幾個poper');
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        $bgMonitor = $container->get('durian.monitor.background');
        $bgMonitor->commandStart('run-card-poper');

        if ($input->getOption('recover-fail')) {
            $poper = new \BB\DurianBundle\Consumer\RecoveryPoper();
        } else {
            $poper = new \BB\DurianBundle\Consumer\Poper();
        }

        $msgNum = $poper->runPop($container, 'card');

        $bgMonitor->setMsgNum($msgNum);
        $bgMonitor->commandEnd();
    }
}
