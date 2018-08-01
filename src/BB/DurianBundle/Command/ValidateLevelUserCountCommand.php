<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 檢查level及level currency的人數是否正確
 */
class ValidateLevelUserCountCommand extends ContainerAwareCommand
{
    /**
     * DB連線
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * 輸出
     *
     * @var OutputInterface
     */
    private $output;

    /**
     * 執行更新人數
     *
     * @var boolean
     */
    private $update;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('durian:validate-level-user-count')
            ->setDescription('檢查level及level currency的人數是否正確')
            ->addOption('level-id-start', null, InputOption::VALUE_OPTIONAL, '檢查的level id起點')
            ->addOption('update', null, InputOption::VALUE_NONE, '執行更新人數')
            ->setHelp(<<<EOT
檢查level及level currency的人數是否正確
app/console durian:validate-level-user-count

檢查指定level起點的level及level currency的人數是否正確
app/console durian:validate-level-user-count --level-id-start=2

更新level及level currency的人數
app/console durian:validate-level-user-count --update
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->conn = $this->getContainer()->get('doctrine.dbal.default_connection');
        $this->output = $output;

        $startTime = microtime(true);

        $levelIdStart = $input->getOption('level-id-start');
        $this->update = $input->getOption('update');

        if (is_null($levelIdStart)) {
            $levelIdStart = 0;
        }

        $levelSql = 'SELECT id, user_count FROM level WHERE id >= ?';
        $statement = $this->conn->executeQuery($levelSql, [$levelIdStart]);

        while ($level = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $levelId = $level['id'];
            $levelUserCount = $level['user_count'];

            $this->validateUserCount($levelId, $levelUserCount);
            $this->output->writeln("validate level id: $levelId finish");
        }

        $this->printPerformance($startTime);
    }

    /**
     * 驗證level及level currency的人數
     *
     * @param integer $levelId 層級id
     * @param integer $levelUserCount 層級的人數
     */
    private function validateUserCount($levelId, $levelUserCount)
    {
        // 記錄錯誤訊息
        $errorMsg = [];

        // 計算各幣別人數
        $userCounts = [];
        $totalUserCount = 0;

        $cashSql = 'SELECT c.currency, COUNT(ul.user_id) AS total ' .
            'FROM cash c ' .
            'JOIN user_level ul ON c.user_id = ul.user_id ' .
            'WHERE ul.level_id = ? ' .
            'GROUP BY c.currency';
        $results = $this->conn->fetchAll($cashSql, [$levelId]);

        foreach ($results as $result) {
            $userCounts[$result['currency']] = $result['total'];
            $totalUserCount += $result['total'];
        }

        // 檢查level的user_count
        if ($levelUserCount != $totalUserCount) {
            $errorMsg[] = "level user_count: $levelUserCount, correct user_count: $totalUserCount";

            if ($this->update) {
                $this->updateLevelUserCount($levelId, $totalUserCount);
            }
        }

        $levelCurrencySql = 'SELECT currency, user_count FROM level_currency WHERE level_id = ?';
        $results = $this->conn->fetchAll($levelCurrencySql, [$levelId]);

        foreach ($results as $result) {
            $currency = $result['currency'];

            // 如果cash統計出沒有該幣別，人數應為0
            if (!isset($userCounts[$currency])) {
                $userCounts[$currency] = 0;
            }

            if ($result['user_count'] != $userCounts[$currency]) {
                $errorMsg[] = sprintf(
                    "level currency: %d user_count: %d, correct user_count: %d",
                    $currency,
                    $result['user_count'],
                    $userCounts[$currency]
                );

                if ($this->update) {
                    $this->updateLevelCurrencyUserCount($levelId, $currency, $userCounts[$currency]);
                }
            }
        }

        if (!empty($errorMsg)) {
            $this->output->writeln("[ERROR]level id: $levelId");

            foreach ($errorMsg as $msg) {
                $this->output->writeln($msg);
            }
        }
    }

    /**
     * 更新level的人數
     *
     * @param integer $levelId 層級id
     * @param integer $userCount 人數
     */
    private function updateLevelUserCount($levelId, $userCount)
    {
        $params = ['user_count' => $userCount];
        $identifier = ['id' => $levelId];

        $this->conn->update('level', $params, $identifier);
    }

    /**
     * 更新level_currency的人數
     *
     * @param integer $levelId 層級id
     * @param integer $currency 幣別
     * @param integer $userCount 人數
     */
    private function updateLevelCurrencyUserCount($levelId, $currency, $userCount)
    {
        $params = ['user_count' => $userCount];
        $identifier = [
            'level_id' => $levelId,
            'currency' => $currency
        ];

        $this->conn->update('level_currency', $params, $identifier);
    }

    /**
     * 印出效能相關訊息
     *
     * @param float $startTime 起始時間
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
