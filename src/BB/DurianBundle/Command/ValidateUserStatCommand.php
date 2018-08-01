<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 會員出入款統計資料檢查
 */
class ValidateUserStatCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * DB連線
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('durian:validate-user-stat')
            ->setDescription('會員出入款統計資料檢查')
            ->addOption('reverse', null, InputOption::VALUE_NONE, '由UserStat反向檢查資料')
            ->setHelp(<<<EOT
檢查會員出入款統計資料
app/console durian:validate-user-stat

由UserStat反向檢查會員出入款統計資料
app/console durian:validate-user-stat --reverse
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->conn = $this->getContainer()->get('doctrine.dbal.default_connection');
        $isReverse = $input->getOption('reverse');

        $startTime = microtime(true);

        if ($isReverse) {
            // 由UserStat反向檢查會員出入款統計資料
            $this->output->writeln('Validate User Stat ...');
            $this->checkUserStat();
            $this->output->writeln("\nValidate User Stat Done\n");
        } else {
            // 檢查出款統計資料
            $this->output->writeln('Validate Withdraw Stat ...');
            $this->checkWithdrawStat();
            $this->output->writeln("\nValidate Withdraw Stat Done\n");
        }

        $this->printPerformance($startTime);
    }

    /**
     * 檢查出款統計資料
     */
    private function checkWithdrawStat()
    {
        $userIdCriteria = 0;

        // 已確認出款的明細才需要統計。因額度是負數，所以最大出款額度取最小
        $sqlWithdrawStat = 'SELECT cwe.user_id, COUNT(cwe.id) AS counts, SUM(real_amount * rate) AS total, ' .
            'MIN(real_amount * rate) AS maxAmount ' .
            'FROM cash_withdraw_entry AS cwe ' .
            'JOIN user AS u ON cwe.user_id = u.id ' .
            'WHERE cwe.user_id > ? AND cwe.status = 1 ' .
            'GROUP BY cwe.user_id ' .
            'ORDER BY cwe.user_id ' .
            'LIMIT 1000';

        $sqlUserStat = 'SELECT user_id, withdraw_count, withdraw_total, withdraw_max ' .
            'FROM user_stat WHERE user_id IN (?)';
        $types = [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY];

        while ($result = $this->conn->fetchAll($sqlWithdrawStat, [$userIdCriteria])) {
            // 整理出款資料
            $withdrawStats = [];
            foreach ($result as $stat) {
                $userId = $stat['user_id'];

                $withdrawStats[$userId]['counts'] = $stat['counts'];

                // 將出款總額四捨五入並轉成正數
                $withdrawStats[$userId]['total'] = abs(number_format($stat['total'], 4, '.', ''));

                // 將最大出款額度四捨五入並轉成正數
                $withdrawStats[$userId]['maxAmount'] = abs(number_format($stat['maxAmount'], 4, '.', ''));
            }

            // 紀錄最後一個userId
            $userIdCriteria = $userId;
            $userInWithdrawStat = array_keys($withdrawStats);

            $statement = $this->conn->executeQuery($sqlUserStat, [$userInWithdrawStat], $types);
            $userInUserStat = [];

            // 檢查userStat的出款資料
            while ($userStat = $statement->fetch(\PDO::FETCH_ASSOC)) {
                $userId = $userStat['user_id'];

                $userInUserStat[] = $userId;
                $withdrawStat = $withdrawStats[$userId];

                $errorMsgs = [];

                // 檢查出款各欄位值
                if ($withdrawStat['counts'] != $userStat['withdraw_count']) {
                    $errorMsgs[] = "withdraw_count: {$userStat['withdraw_count']}, " .
                        "new withdraw_count: {$withdrawStat['counts']}";
                }

                if ($withdrawStat['total'] != $userStat['withdraw_total']) {
                    $errorMsgs[] = "withdraw_total: {$userStat['withdraw_total']}, " .
                        "new withdraw_total: {$withdrawStat['total']}";
                }

                if ($withdrawStat['maxAmount'] != $userStat['withdraw_max']) {
                    $errorMsgs[] = "withdraw_max: {$userStat['withdraw_max']}, " .
                        "new withdraw_max: {$withdrawStat['maxAmount']}";
                }

                // 輸出錯誤的統計資訊
                if (!empty($errorMsgs)) {
                    $this->output->writeln("\n[ERROR] UserStat User: {$userId}");
                    $this->printErrorMsg($errorMsgs);
                }
            }

            // 檢查userStat是否存在
            $userDiff = array_diff($userInWithdrawStat, $userInUserStat);

            // 輸出userStat不存在的user
            foreach ($userDiff as $userId) {
                $this->output->writeln("\nUser: $userId UserStat Not Exist");
            }
        }
    }

    /**
     * 檢查會員出入款統計資料
     */
    private function checkUserStat()
    {
        // 檢查沒有出款統計的會員出入款統計資料
        $userIdCriteria = 0;
        $sqlCWE = 'SELECT us.user_id, withdraw_count, withdraw_total, withdraw_max ' .
            'FROM user_stat AS us ' .
            'LEFT JOIN cash_withdraw_entry AS cwe ON us.user_id = cwe.user_id AND cwe.status = 1 ' .
            'WHERE cwe.id IS NULL AND us.user_id > ? ' .
            'ORDER BY us.user_id ' .
            'LIMIT 1000';

        while ($userStats = $this->conn->fetchAll($sqlCWE, [$userIdCriteria])) {
            foreach ($userStats as $userStat) {
                $userIdCriteria = $userStat['user_id'];
                $errorMsgs = [];

                if ($userStat['withdraw_count'] != 0) {
                    $errorMsgs[] = "withdraw_count: {$userStat['withdraw_count']}, new withdraw_count: 0";
                }

                if ($userStat['withdraw_total'] != 0) {
                    $errorMsgs[] = "withdraw_total: {$userStat['withdraw_total']}, new withdraw_total: 0";
                }

                if ($userStat['withdraw_max'] != 0) {
                    $errorMsgs[] = "withdraw_max: {$userStat['withdraw_max']}, new withdraw_max: 0";
                }

                // 輸出錯誤的統計資訊
                if (!empty($errorMsgs)) {
                    $this->output->writeln("\n[ERROR] UserStat User: {$userStat['user_id']}");
                    $this->printErrorMsg($errorMsgs);
                }
            }
        }

        // 檢查是否有多餘的userStat
        $userIdCriteria = 0;
        $sqlSubStat = 'SELECT us.user_id ' .
            'FROM user_stat AS us ' .
            'WHERE us.user_id > ? ' .
            'ORDER BY us.user_id ' .
            'LIMIT 1000';

        $sqlWithdraw = 'SELECT DISTINCT cwe.user_id ' .
            'FROM cash_withdraw_entry AS cwe ' .
            'JOIN user AS u ON cwe.user_id = u.id ' .
            'WHERE user_id IN (?) AND status = 1';
        $types = [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY];

        while ($result = $this->conn->fetchAll($sqlSubStat, [$userIdCriteria])) {
            $userIds = [];
            foreach ($result as $user) {
                $userIds[] = $user['user_id'];
            }
            // 紀錄最後一個userId
            $userIdCriteria = end($userIds);

            $withdrawuserIds = [];

            // 取出出款資料的user
            $result = $this->conn->fetchAll($sqlWithdraw, [$userIds], $types);
            foreach ($result as $user) {
                $withdrawuserIds[] = $user['user_id'];
            }

            $userDiff = array_diff($userIds, $withdrawuserIds);

            // 輸出userStat多餘的user
            foreach ($userDiff as $userId) {
                $this->output->writeln("\nUser: $userId UserStat Is Unnecessary");
            }
        }
    }

    /**
     * 印出錯誤的統計資訊
     *
     * @param array $errorMsgs 錯誤的統計訊息
     */
    private function printErrorMsg($errorMsgs)
    {
        foreach ($errorMsgs as $msg) {
            $this->output->writeln($msg);
        }
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
