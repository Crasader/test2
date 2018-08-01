<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\StatDomainDepositAmount;

/**
 * 同步廳的當日入款總金額
 */
class SyncDomainDepositAmountCommand extends ContainerAwareCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:sync-domain-deposit-amount')
            ->setDescription('同步廳的當日入款總金額')
            ->setHelp(<<<EOT
同步廳的當日入款總金額
$ app/console durian:sync-domain-deposit-amount
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $executeStart = microtime(true);
        $this->setUpLogger();
        $this->log('Start.');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');

        $bgMonitor->commandStart('sync-domain-deposit-amount');
        $excuteCount = 0;

        $statQueues = $this->getQueue();

        foreach ($statQueues as $statQueue) {
            try {
                $domain = $statQueue['domain'];
                $confirmAt = new \DateTime($statQueue['confirm_at']);

                // 需轉換為美東時間, 格式保留Ymd
                $usETimeZone = new \DateTimeZone('Etc/GMT+4');
                $confirmAt->setTimezone($usETimeZone);
                $at = $confirmAt->format('Ymd');

                $amount = $statQueue['amount'];

                $criteria = [
                    'domain' => $domain,
                    'at' => $at,
                ];
                $stat = $em->getRepository('BBDurianBundle:StatDomainDepositAmount')->findOneBy($criteria);

                if (!$stat) {
                    $stat = new StatDomainDepositAmount($domain, $at);
                    $em->persist($stat);
                    $em->flush();
                }

                // 額度累加
                $oldAmount = $stat->getAmount();
                $stat->setAmount($oldAmount + $amount);
                $newAmount = $stat->getAmount();

                $em->flush();

                // 每達到500萬需送異常入款提醒
                if ($this->checkAmount($oldAmount, $newAmount)) {
                    $redis->rpush('domain_abnormal_deposit_notify_queue', json_encode($criteria));
                }

                $excuteCount++;
            } catch (\Exception $e) {
                $redis = $this->getContainer()->get('snc_redis.default_client');

                $redis->rpush('stat_domain_deposit_queue', json_encode($statQueue));

                $exception = [
                    'result' => 'error',
                    'code' => $e->getCode(),
                    'message' => $e->getMessage()
                ];
                $output->writeln(json_encode($exception));
            }
        }

        $output->writeln("$excuteCount queue processed.");

        $bgMonitor->setMsgNum($excuteCount);
        $bgMonitor->commandEnd();

        $this->printPerformance($executeStart);
        $this->log('Finish.');
    }

    /**
     * 取得廳的入款金額
     *
     * @return ArrayCollection
     */
    private function getQueue()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $statAmounts = [];

        $count = 0;

        while ($count < 1000) {
            $statAmount = json_decode($redis->rpop('stat_domain_deposit_queue'), true);

            if (!$statAmount) {
                break;
            }

            $statAmounts[] = $statAmount;
            ++$count;
        }

        return $statAmounts;
    }

    /**
     * 檢查廳的入款總金額，每達到500萬需送異常入款提醒
     *
     * @param float $old 原金額
     * @param float $new 新金額
     *
     * @return boolean 是否到達寄發門檻
     */
    private function checkAmount($old, $new)
    {
        $abnormalAmount = 5000000;

        $check = floor($new / $abnormalAmount) - floor($old / $abnormalAmount);

        if ($check > 0) {
            return true;
        }

        return false;
    }

    /**
     * 設定logger
     */
    private function setUpLogger()
    {
        $this->logger = $this->getContainer()->get('durian.logger_manager')
            ->setUpLogger('sync_domain_deposit_amount.log');
    }

    /**
     * 記錄log
     *
     * @param string $msg
     */
    private function log($msg)
    {
        $this->logger->addInfo($msg);
    }

    /**
     * 印出效能相關訊息
     *
     * @param integer $startTime
     */
    private function printPerformance($startTime)
    {
        $endTime = microtime(true);
        $excutionTime = round($endTime - $startTime, 1);
        $timeString = $excutionTime . ' sec.';

        if ($excutionTime > 60) {
            $timeString = round($excutionTime / 60, 0) . ' mins.';
        }

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);

        $this->log("[Performance]");
        $this->log("Time: $timeString");
        $this->log("Memory: $usage mb");
    }
}
