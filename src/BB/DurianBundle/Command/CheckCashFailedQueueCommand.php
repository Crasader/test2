<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\CashEntry;

class CheckCashFailedQueueCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('durian:tools:check-cash-failed-queue')
            ->setDescription('檢查 cash_failed_queue 的資料是否異常');
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $redis = $this->getContainer()->get('snc_redis.default');
        $em = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        $output->writeln('開始時間: ' . date('Y-m-d H:i:s'));

        $failedQueueKey = 'cash_failed_queue';

        $total = $redis->llen($failedQueueKey);

        $insertSql = [];
        $insertHisSql = [];

        $output->writeln('總筆數: ' . $total);

        for ($i = 0; $i < $total; $i++) {
            $data = $redis->lindex($failedQueueKey, $i);
            $queue = json_decode($data, true);

            // 遇到有缺資料的情況，顯示錯誤並繼續處理其它
            if (!isset($queue['TABLE'])) {
                $output->writeln('Redis Index: ' . $i . ' ERROR');
                continue;
            }

            $table = $queue['TABLE'];

            // 排除掉非現金明細
            if ($table != 'cash_entry') {
                continue;
            }

            $head = $queue['HEAD'];

            // 非 INSERT 語法不處理
            if ($head != 'INSERT') {
                continue;
            }

            $id = $queue['id'];
            $at = $queue['at'];

            $criteria = [
                'id' => $id,
                'at' => $at
            ];
            $entry = $em->find('BBDurianBundle:CashEntry', $criteria);
            $entryHis = $emHis->find('BBDurianBundle:CashEntry', $criteria);

            $output->write('Redis Index: '. $i . ' / Entry Id: '. $id . ' Result: ');

            $error = [];

            if (!$entry) {
                $error[] = '現行資料庫無資料';
                $insertSql[] = $this->generateInsertSql($queue);
            }

            if (!$entryHis) {
                $error[] = '歷史資料庫無資料';
                $insertHisSql[] = $this->generateInsertSql($queue);
            }

            if ($entry) {
                if (!$this->isMatched($entry, $queue)) {
                    $error[] = '與現行資料庫不同';
                } else {
                    $error[] = '與現行資料庫比對相同';
                }
            }

            if ($entryHis) {
                if (!$this->isMatched($entryHis, $queue)) {
                    $error[] = '與歷史資料庫不同';
                } else {
                    $error[] = '與歷史資料庫比對相同';
                }
            }

            $result = 'OK';

            if ($error) {
                 $result = implode(', ', $error);
            }

            $output->writeln($result);

            if ($i % 100 == 0) {
                $em->clear();
            }
        }

        $em->clear();

        $output->writeln('-----------------------------------------');

        // 產生語法
        $output->writeln('產生新增到現行資料庫的語法:');

        foreach ($insertSql as $sql) {
            $output->writeln($sql);
        }

        $output->writeln('產生新增到歷史資料庫的語法:');

        foreach ($insertHisSql as $sql) {
            $output->writeln($sql);
        }

        $output->writeln('結束時間: ' . date('Y-m-d H:i:s'));
    }

    /**
     * 產生新增明細語法
     *
     * @param array $entry
     * @return string
     */
    private function generateInsertSql(array $entry)
    {
        unset($entry['HEAD']);
        unset($entry['TABLE']);
        unset($entry['ERRCOUNT']);

        $columns = array_keys($entry);

        $sql = 'INSERT INTO cash_entry (' . implode(',', $columns) . ') VALUES';
        $sql .= " ('" . implode("','", array_values($entry)) . "');";

        return $sql;
    }

    /**
     * 檢查明細內容是否相同
     *
     * @param CashEntry $entryDB
     * @param array $entryRedis
     *
     * @return boolean
     */
    private function isMatched($entryDB, array $entryRedis)
    {
        if ($entryDB->getId() != $entryRedis['id']) {
            return false;
        }

        if ($entryDB->getCashId() != $entryRedis['cash_id']) {
            return false;
        }

        if ($entryDB->getUserId() != $entryRedis['user_id']) {
            return false;
        }

        if ($entryDB->getCurrency() != $entryRedis['currency']) {
            return false;
        }

        if ($entryDB->getOpcode() != $entryRedis['opcode']) {
            return false;
        }

        $amount = round($entryRedis['amount'], 4);
        if ($entryDB->getAmount() != $amount) {
            return false;
        }

        if ($entryDB->getMemo() != $entryRedis['memo']) {
            return false;
        }

        $balance = round($entryRedis['balance'], 4);
        if ($entryDB->getBalance()!= $balance) {
            return false;
        }

        if ($entryDB->getRefId() != $entryRedis['ref_id']) {
            return false;
        }

        if ($entryDB->getCreatedAt()->format('Y-m-d H:i:s')!= $entryRedis['created_at']) {
            return false;
        }

        return true;
    }
}