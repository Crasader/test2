<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * 轉移公司入款紀錄資料
 */
class MigrateRemitEntryCommand extends ContainerAwareCommand
{
    /**
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:migrate:remit-entry')
            ->setDescription('轉移公司入款紀錄資料')
            ->addArgument('end_id', InputArgument::REQUIRED, '要更新到的最大Id')
            ->addOption('sleep', null, InputOption::VALUE_NONE, '每執行1000筆休息1秒')
            ->setHelp(<<<EOT
轉移公司入款紀錄資料, 轉到小於等於指定ID的資料
app/console durian:migrate:remit-entry 80000000

平日轉移公司入款紀錄資料, 轉到小於等於指定ID的資料
app/console durian:migrate:remit-entry 80000000 --sleep
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);
        $this->setUpLogger();
        $conn = $this->getContainer()->get('doctrine.dbal.default_connection');

        $this->log('Start Insert Remit Entry New...');

        // 取得新公司入款紀錄資料的最大id, 當作轉移資料的起始id
        $sqlStartId = 'SELECT MAX(id) FROM remit_entry_new';
        $startId = $conn->fetchColumn($sqlStartId);
        if ($startId === null) {
            $startId = 0;
        }

        $remitNum = 0;
        $endId = $input->getArgument('end_id');

        $sqlNextId = 'SELECT MAX(id) FROM (SELECT id FROM remit_entry ' .
            'WHERE id > ? AND id <= ? ORDER BY id ASC LIMIT 1000) sub';

        $remitQuery = "INSERT INTO remit_entry_new " .
            "SELECT " .
            "re.id, re.remit_account_id, IFNULL(ra.domain, 0), re.user_id, re.confirm_at, re.created_at, " .
            "re.order_number, re.abandon_discount, re.auto_confirm, (CASE WHEN re.bb_auto_confirm = 1 THEN 2 WHEN " .
            "re.auto_confirm = 1 AND re.bb_auto_confirm = 0 THEN 1 ELSE 0 END), re.method, re.status, " .
            "re.level_id, re.duration, re.ancestor_id, re.bank_info_id, re.amount_entry_id, re.discount_entry_id, " .
            "re.other_discount_entry_id, re.amount, re.discount, re.other_discount, re.actual_other_discount, " .
            "re.rate, re.deposit_at, re.trade_number, re.transfer_code, re.atm_terminal_code, re.identity_card, " .
            "re.old_order_number, re.cellphone, re.username, re.payer_card, re.operator, re.name_real, re.branch, " .
            "re.memo, re.version " .
            "FROM remit_entry re " .
            "LEFT JOIN remit_account ra ON re.remit_account_id = ra.id " .
            "WHERE re.id > ? AND re.id <= ?";

        while ($startId < $endId) {
            $nextId = $conn->fetchColumn($sqlNextId, [$startId, $endId]);
            if ($nextId === null) {
                break;
            }

            $remitNum += $conn->executeUpdate($remitQuery, [$startId, $nextId]);

            // 平日轉移用
            if ($input->getOption('sleep')) {
                sleep(1);
            }

            $startId = $nextId;
        }

        $this->log("remit_entry_new insert total num: {$remitNum}");
        $this->printPerformance($startTime);
        $this->log("Insert Remit Entry New Finish\n");
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

    /**
     * 設定logger
     */
    private function setUpLogger()
    {
        $this->logger = $this
            ->getContainer()
            ->get('durian.logger_manager')
            ->setUpLogger('migrate_remit_entry.log');
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
}
