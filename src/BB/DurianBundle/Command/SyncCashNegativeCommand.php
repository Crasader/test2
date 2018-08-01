<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\CashNegative;

/**
 * 同步負數現金
 *
 * @author Chuck 2016.11.17
 */
class SyncCashNegativeCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:sync-cash-negative')
            ->setDescription('同步負數現金')
            ->setHelp(<<<EOT
同步負數現金
app/console durian:sync-cash-negative
EOT
             );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart('sync-cash-negative');

        $this->output = $output;

        $time = new \DateTime;
        $msgNum = $this->process();
        $this->end($time);

        $bgMonitor->setMsgNum($msgNum);
        $bgMonitor->commandEnd();
    }

    /**
     * 處理佇列
     *
     * @return integer
     */
    private function process()
    {
        $redis = $this->getRedis();
        $em = $this->getEntityManager();
        $count = 0;

        $queues = [];

        try {
            while ($count++ < 1000) {
                $queue = $redis->rpop('cash_negative_queue');

                if (!$queue) {
                    break;
                }

                $negMsg = json_decode($queue, true);

                if (!isset($negMsg['user_id']) || !isset($negMsg['currency'])) {
                    continue;
                }

                $this->output->writeln($queue);

                $queues[] = $queue;

                $userId = $negMsg['user_id'];
                $currency = $negMsg['currency'];
                $params = [
                    'userId' => $userId,
                    'currency' => $currency
                ];

                $negEntity = $em->find('BBDurianBundle:CashNegative', $params);

                if (!$negEntity && !isset($negMsg['id'])) {
                    continue;
                }

                if (!$negEntity) {
                    $negEntity = new CashNegative($userId, $currency);
                    $negEntity->setCashId($negMsg['cash_id']);
                    $em->persist($negEntity);
                }

                $version = $negEntity->getVersion();

                $updated = !$version || $version < $negMsg['cash_version'];

                if ($updated) {
                    $negEntity->setBalance($negMsg['balance']);
                    $negEntity->setVersion($negMsg['cash_version']);
                }

                if (isset($negMsg['id'])) {
                    $negEntity->setEntryId($negMsg['id']);
                    $negEntity->setAt($negMsg['at']);
                    $negEntity->setOpcode($negMsg['opcode']);
                    $negEntity->setAmount($negMsg['amount']);
                    $negEntity->setEntryBalance($negMsg['balance']);
                    $negEntity->setRefId($negMsg['ref_id']);
                    $negEntity->setMemo($negMsg['memo']);
                }
            }

            $em->flush();
        } catch (\Exception $e) {
            $exception = [
                'time'    => date('Y-m-d H:i:s'),
                'result'  => 'error',
                'code'    => $e->getCode(),
                'message' => $e->getMessage()
            ];
            $this->output->writeln(print_r($exception, true));

            foreach ($queues as $q) {
                $redis->lpush('cash_negative_queue', $q);
            }
        }

        return $count;
    }

    /**
     * 程式結束顯示處理時間、記憶體
     *
     * @param \DateTime $startTime 開始時間
     */
    private function end(\DateTime $startTime)
    {
        $endTime = new \DateTime;
        $costTime = $endTime->diff($startTime, true);
        $this->output->writeln('Execute time: ' . $costTime->format('%H:%I:%S'));

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);
        $this->output->writeln("Memory MAX use: $usage M");
    }

    /**
     * 回傳 Redis 操作物件
     *
     * @param string $name Redis名稱
     * @return \Predis\Client
     */
    private function getRedis($name = 'default')
    {
        return $this->getContainer()->get("snc_redis.{$name}");
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
}
