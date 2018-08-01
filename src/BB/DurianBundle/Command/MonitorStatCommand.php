<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Monolog\Logger;

/**
 * 監控統計相關背景
 *
 * @author Evan 2016.08.25
 */
class MonitorStatCommand extends ContainerAwareCommand
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
     * @var Logger
     */
    private $logger;

    /**
     * 因exec 為php 內建函式無法mock，用來指定測試碼exec 結果
     *
     * @var array
     */
    private $testExecOut = [];

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:monitor-stat')
            ->setDescription('監控統計相關背景')
            ->addOption('hk', null, InputOption::VALUE_NONE, '統計香港背景')
            ->addOption('memory', null, InputOption::VALUE_NONE, '監控記憶體用量')
            ->addOption('over-time', null, InputOption::VALUE_NONE, '執行時間是否超過預計結束時間')
            ->setHelp(<<<EOT
監控統計相關背景,

監控統計相關背景記憶體用量，美東時區
$ ./console durian:monitor-stat --memory

監控統計相關背景執行時間是否超過預計時間，美東時區
$ ./console durian:monitor-stat --over-time

監控統計相關背景記憶體用量，香港時區
$ ./console durian:monitor-stat --hk --memory
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

        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart('monitor-stat');

        if ($input->getOption('memory')) {
            $this->mointorMemory();
        }

        if ($input->getOption('over-time')) {
            $this->checkOverTime();
        }

        $bgMonitor->commandEnd();
    }

    /**
     * 取得bin檔對應的參數
     *
     * @param string $timeZone 時區
     * @return array
     */
    private function getMemoryLimit($timeZone = null)
    {
        $bin = 'stat_all';

        if ($timeZone == 'hk') {
            $bin = 'stat_all_hk';
        }

        // 抓bin檔的記憶體限制大小
        exec("grep memory_limit bin\/{$bin} | grep -v echo", $out);
        $memoryLimit = [];

        // 將每個背景的記憶體限制存下來
        foreach ($out as $out) {
            $process = explode(' ', $out);
            preg_match_all('/\d+/', $process[6], $matches);
            $memoryLimit[$process[8]] = $matches[0][0];
        }

        return $memoryLimit;
    }

    /**
     * 監控記憶體用量
     */
    private function mointorMemory()
    {
        $this->setUpLogger('monitor-stat');

        $memoryLimit = $this->getMemoryLimit();

        if ($this->input->getOption('hk')) {
            $memoryLimit = $this->getMemoryLimit('hk');
        }

        foreach ($memoryLimit as $key => $value) {
            $out = [];
            exec("ps -eo rss,command | grep -v grep | grep console | grep $key", $out);

            if ($this->getContainer()->getParameter('kernel.environment') == 'test') {
                $out = $this->testExecOut;
            }

            if (!$out) {
                continue;
            }

            // 取得正在執行的背景記憶體用量與限制
            $data = explode(' ', $out[0]);
            $memoryUsage = $data[0] / 1024;
            $limit = $memoryLimit[$key];

            $command = $key;

            if ($this->input->getOption('hk')) {
                $command .= '-hk';
            }

            // 記憶體使用量與記憶體限制差距100MB，送訊息到italking
            if (abs($memoryUsage - $limit) <= 100 || $limit - $memoryUsage < 0) {
                $italkingOperator = $this->getContainer()->get('durian.italking_operator');

                $now = date('Y-m-d H:i:s');
                $msg = "[$now] 統計背景 {$command}，記憶體使用量已達 $memoryUsage M，記憶體上限為 $limit M，" .
                    "請通知 RD5-帳號研發部值班人員檢查";

                $italkingOperator->pushMessageToQueue('acc_system', $msg);

                $this->log($msg);
            }
        }

        $this->logger->popHandler()->close();
    }

    /**
     * 取得所有統計背景名稱
     *
     * @return array
     */
    private function getStatCommands()
    {
        $bin = 'stat_all';
        $commands = [];

        exec("grep memory_limit bin\/{$bin} | grep -v echo", $outs);

        foreach ($outs as $out) {
            $process = explode(' ', $out);
            $commands[] = $process[8];
        }

        return $commands;
    }

    /**
     * 檢查執行時間是否逾時
     */
    private function checkOverTime()
    {
        $this->setUpLogger('monitor-stat');

        $statCommands = $this->getStatCommands();

        foreach ($statCommands as $command) {
            $out = [];
            exec("ps -eo command | grep -v grep |grep console | grep {$command}", $out);

            if ($this->getContainer()->getParameter('kernel.environment') == 'test') {
                $out = $this->testExecOut;
            }

            if ($out) {
                $italkingOperator = $this->getContainer()->get('durian.italking_operator');
                $now = date('Y-m-d H:i:s');

                if (strpos($out[0], 'hk')) {
                    $command .= '-hk';
                }

                $msg = "[$now] 統計背景 {$command}，執行時間已逾時，請通知 RD5-帳號研發部值班人員檢查";

                $italkingOperator->pushMessageToQueue('acc_system', $msg);

                $this->log($msg);
            }
        }

        $this->logger->popHandler()->close();
    }

    /**
     * 設定logger
     */
    private function setUpLogger()
    {
        $logger = $this->getContainer()->get('logger');
        $handler = $this->getContainer()->get('monolog.handler.monitor_stat');
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
