<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * 轉移入款紀錄明細資料驗證
 */
class MigrateCashDepositEntryVerificationCommand extends ContainerAwareCommand
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
            ->setName('durian:migrate:cash-deposit-entry-verification')
            ->setDescription('轉移入款紀錄明細資料驗證')
            ->addArgument('end_id', InputArgument::REQUIRED, '要更新到的最大Id')
            ->addOption('sleep', null, InputOption::VALUE_NONE, '每執行10000筆休息1秒')
            ->addOption('start_id', null, InputOption::VALUE_OPTIONAL, '起始Id')
            ->setHelp(<<<EOT
轉移入款紀錄明細資料驗證, 驗證到小於等於指定ID的資料, 格式為西元年月日加10碼
app/console durian:migrate:cash-deposit-entry-verification 201601010000000000

平日轉移入款紀錄明細資料驗證, 驗證到小於等於指定ID的資料, 格式為西元年月日加10碼
app/console durian:migrate:cash-deposit-entry-verification 201601010000000000 --sleep --start_id=123
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $startTime = microtime(true);
        $conn = $this->getContainer()->get('doctrine.dbal.default_connection');

        $output->writeln("Start Verify New Cash Deposit Entry...");

        $startId = 0;
        if ($input->getOption('start_id')) {
            $startId = $input->getOption('start_id');
        }

        $endId = $input->getArgument('end_id');

        $sqlColumn = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'cash_deposit_entry_old'";
        $columns = $conn->fetchAll($sqlColumn);
        unset($columns[0]); // id
        unset($columns[1]); // at

        $where = [];
        foreach ($columns as $column) {
            $where[] = "old.{$column['COLUMN_NAME']} != new.{$column['COLUMN_NAME']}";
        }

        $sqlNextId = 'SELECT MAX(id) FROM (SELECT id FROM cash_deposit_entry_old ' .
            'WHERE id > ? AND id <= ? ORDER BY id ASC LIMIT 10000) sub';

        $verifyQuery = "SELECT old.id FROM cash_deposit_entry_old old " .
            "LEFT JOIN cash_deposit_entry new ON (old.id = new.id and old.at = new.at) " .
            "WHERE old.id > ? AND old.id <= ? AND (ISNULL(new.id) OR " . implode(' OR ', $where) . ")";

        while ($startId < $endId) {
            $nextId = $conn->fetchColumn($sqlNextId, [$startId, $endId]);
            if ($nextId === null) {
                break;
            }

            $diff = $conn->fetchAll($verifyQuery, [$startId, $nextId]);

            // 平日驗證用
            if ($input->getOption('sleep')) {
                sleep(1);
            }

            $startId = $nextId;
            if ($diff) {
                foreach ($diff as $row) {
                    $output->writeln($row['id']);
                }
            }
        }

        $this->printPerformance($startTime);
        $output->writeln("Verify Cash Deposit Entry Finish");
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

        $this->output->writeln("[Performance]");
        $this->output->writeln("Time: $timeString");
        $this->output->writeln("Memory: $usage mb");
    }
}
