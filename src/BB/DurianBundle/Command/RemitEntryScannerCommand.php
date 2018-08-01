<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * 檢查公司入款紀錄轉移資料
 */
class RemitEntryScannerCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @see Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:remit-entry-scanner')
            ->setDescription('公司入款明細轉移資料檢查')
            ->addArgument('end_id', InputArgument::REQUIRED, '要更新到的最大Id')
            ->addOption('start_id', null, InputOption::VALUE_OPTIONAL, '起始Id')
            ->addOption('sleep', null, InputOption::VALUE_NONE, '每執行1000筆休息1秒')
            ->setHelp(<<<EOT
檢查公司入款紀錄資料, 驗到小於等於指定ID的資料
app/console durian:remit-entry-scanner 80000000

平日檢查公司入款紀錄資料, 驗到小於等於指定ID的資料
app/console durian:remit-entry-scanner 80000000 --sleep --start_id=123
EOT
            );
    }

    /**
     * @see Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $startTime = microtime(true);
        $conn = $this->getContainer()->get('doctrine.dbal.default_connection');

        $endId = $input->getArgument('end_id');
        $startId = 0;

        if ($input->getOption('start_id')) {
            $startId = $input->getOption('start_id');
        }

        $sqlColumn = "SELECT COLUMN_NAME, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'remit_entry'";
        $columns = $conn->fetchAll($sqlColumn);
        unset($columns[0]); // id

        $where = [];
        $select = [];
        $fields = [];
        foreach ($columns as $column) {
            // 新表bb_auto_confirm是移除的，所以無需檢查
            if ($column['COLUMN_NAME'] === 'bb_auto_confirm') {
                continue;
            }

            $fields[] = $column['COLUMN_NAME'];
            $where[] = "re.{$column['COLUMN_NAME']} != ren.{$column['COLUMN_NAME']}";
            $select[] = "ren.{$column['COLUMN_NAME']} as {$column['COLUMN_NAME']}_new";

            // 如果該欄位為nullable，則要另外判斷檢查
            if ($column['IS_NULLABLE'] === 'YES') {
                $where[] = "(re.{$column['COLUMN_NAME']} IS NULL AND ren.{$column['COLUMN_NAME']} IS NOT NULL)";
                $where[] = "(re.{$column['COLUMN_NAME']} IS NOT NULL AND ren.{$column['COLUMN_NAME']} IS NULL)";
            }
        }

        // 檢查轉移時自動認款平台id是否正確
        $where[] = 'ren.auto_remit_id != (CASE WHEN re.bb_auto_confirm = 1 THEN 2 WHEN re.auto_confirm = 1 ' .
            'AND re.bb_auto_confirm = 0 THEN 1 ELSE 0 END)';

        // 檢查轉移時廳主是否正確
        $where[] = 'ren.domain != ra.domain';

        // 檢查轉移資料時domain是否為null
        $where[] = 'ra.domain IS NULL';

        // 新增檢查domain
        $fields[] = 'domain';

        // 新增檢查自動認款平台id
        $fields[] = 'auto_remit_id';

        $sqlNextId = 'SELECT MAX(id) FROM (SELECT id FROM remit_entry ' .
            'WHERE id > ? AND id <= ? ORDER BY id ASC LIMIT 10000) sub';

        $sqlScan = 'SELECT re.*, ren.id AS id_new, ra.domain, ren.domain AS domain_new, ' .
            'ren.auto_remit_id AS auto_remit_id_new, ' .
            '(CASE WHEN re.bb_auto_confirm = 1 THEN 2 WHEN re.auto_confirm = 1 ' .
            'AND re.bb_auto_confirm = 0 THEN 1 ELSE 0 END) AS auto_remit_id, ' . implode(', ', $select) . ' ' .
            'FROM remit_entry re ' .
            'LEFT JOIN remit_entry_new ren ON re.id = ren.id ' .
            'LEFT JOIN remit_account ra ON re.remit_account_id = ra.id ' .
            'WHERE re.id > ? AND re.id <= ? AND (ren.id IS NULL OR ' . implode(' OR ', $where) . ')';

        while ($nextId = $conn->fetchColumn($sqlNextId, [$startId, $endId])) {
            if ($nextId > $endId) {
                break;
            }

            $diffs = $conn->fetchAll($sqlScan, [$startId, $nextId]);

            if ($diffs) {
                $this->compareData($diffs, $fields);
            }

            // 平日驗證用
            if ($input->getOption('sleep')) {
                sleep(1);
            }
            $startId = $nextId;
        }
        $this->output->writeln('Compare RemitEntry Done.');

        $this->printPerformance($startTime);
    }

    /**
     * 比較remit_entry和remit_entry_new資料
     *
     * @param array $diffs
     * @param array $fields
     */
    private function compareData($diffs, $fields)
    {
        foreach ($diffs as $diff) {
            // 檢查remit_entry_new有沒有明細, id_new為空值代表漏轉
            if (empty($diff['id_new'])) {
                $this->output->writeln("RemitEntryNew not found, Id: {$diff['id']}");

                continue;
            }

            // 輸出差異
            $this->output->writeln("Diff Id: {$diff['id']}");
            foreach ($fields as $field) {
                $newField = $field . "_new";

                if ($diff[$field] != $diff[$newField]) {
                    $this->output->writeln("{$field} old: {$diff[$field]}, new: {$diff[$newField]}");
                }
            }
        }
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
        $this->output->writeln("\nExecute time: $timeString");

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);
        $this->output->writeln("Memory MAX use: $usage M");
    }
}
