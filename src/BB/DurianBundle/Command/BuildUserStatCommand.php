<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 統計會員資料
 */
class BuildUserStatCommand extends ContainerAwareCommand
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
     * 目前的DB連線設定
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:build-user-stat')
            ->setDescription('統計會員資料')
            ->setHelp(<<<EOT
統計會員出入款次數及金額
app/console durian:build-user-stat
EOT
             );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);

        $this->input = $input;
        $this->output = $output;

        // init
        $this->conn = $this->getContainer()->get('doctrine.dbal.default_connection');

        // 統計Withdraw
        $this->StatWithdraw();

        $this->printPerformance($startTime);
    }

    /**
     * 統計出款紀錄
     */
    private function StatWithdraw()
    {
        $this->output->writeln('Build Withdraw Stat Start...');

        $userIdCriteria = 0;

        $sourceSql = 'SELECT cwe.user_id, SUM(cwe.real_amount * cwe.rate * -1) AS amount, ';
        $sourceSql .= 'count(cwe.id) AS counts, MAX(cwe.real_amount * cwe.rate * -1) AS max_amount ';
        $sourceSql .= 'FROM cash_withdraw_entry cwe ';
        $sourceSql .= 'JOIN user u on cwe.user_id = u.id WHERE cwe.user_id > ? AND status = 1 ';
        $sourceSql .= 'GROUP BY cwe.user_id ORDER BY cwe.user_id LIMIT 1000';

        $statSql = 'SELECT user_id FROM user_stat WHERE user_id IN (?)';
        $types = [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY];

        while ($result = $this->conn->fetchAll($sourceSql, [$userIdCriteria])) {
            $sourceStats = [];

            // 整理統計資料
            foreach ($result as $stat) {
                $userIdCriteria = $stat['user_id'];

                $sourceStats[$userIdCriteria]['counts'] = $stat['counts'];
                $sourceStats[$userIdCriteria]['total'] = number_format($stat['amount'], 4, '.', '');
                $sourceStats[$userIdCriteria]['max_amount'] = number_format($stat['max_amount'], 4, '.', '');
            }
            $sourceUsers = array_keys($sourceStats);

            // 檢查統計資料是否存在
            $userStats = $this->conn->fetchAll($statSql, [$sourceUsers], $types);

            $userIds = [];
            foreach ($userStats as $userStat) {
                $userIds[] = $userStat['user_id'];
            }

            // 檢查userStat是否存在
            $userDiff = array_diff($sourceUsers, $userIds);

            $insertSql = [];

            // userStat不存在則新增統計資料
            foreach ($userDiff as $userId) {
                $stat = $sourceStats[$userId];

                $values = [
                    $userId,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    $stat['counts'],
                    $stat['total'],
                    $stat['max_amount']
                ];
                $insertSql[] = '( ' . implode(', ', $values) . ')';
            }

            $sql = '';

            if (count($insertSql)) {
                $sql .= 'INSERT INTO user_stat (user_id, ';
                $sql .= 'deposit_count, deposit_total, deposit_max, ';
                $sql .= 'manual_count, manual_total, manual_max, ';
                $sql .= 'remit_count, remit_total, remit_max, ';
                $sql .= 'withdraw_count, withdraw_total, withdraw_max ';
                $sql .= ') VALUES ';
                $sql .= implode(',', $insertSql) . ';';
            }

            // userStat存在則更新統計資料
            foreach ($userIds as $userId) {
                $stat = $sourceStats[$userId];

                $sql .= "UPDATE user_stat SET withdraw_count = {$stat['counts']}, " .
                    "withdraw_total = {$stat['total']}, " .
                    "withdraw_max = {$stat['max_amount']} " .
                    "WHERE user_id = $userId;";
            }

            if ($sql != '') {
                $this->conn->exec($sql);
            }
        }

        $this->output->writeln('Build Withdraw Stat End.');
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
