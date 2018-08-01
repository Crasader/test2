<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 清除無效的公司入款層級資料
 */
class ClearRemitAccountLevelCommand extends ContainerAwareCommand
{
    /**
     * 來源DB連線
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $sourceConn;

    /**
     * 目標DB連線
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * domain和oldLevel對應levelId
     *
     * @var array
     */
    private $map = [];

    /**
     * 收集無效的remit_account_level
     */
    private $ralSet = [];

    /**
     * 連線設定
     *
     * @var Array
     */
    private $config = [
        'host' => '',
        'dbname' => 'SPORT_MEM',
        'port' => '3306',
        'user' => '',
        'password' => '',
        'charset' => 'utf8',
        'driver' => 'pdo_mysql'
    ];

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:clear:remit-account-level')
            ->setDescription('清除無效的公司入款層級資料');
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);
        $this->setUpLogger();
        $this->sourceConn = \Doctrine\DBAL\DriverManager::getConnection($this->config);
        $this->conn = $this->getContainer()->get('doctrine.dbal.default_connection');

        // 取得研一層級資料放入map
        $sqlLevel = 'select HallId, LevelId from TransferLimitByHall';
        $statement = $this->sourceConn->executeQuery($sqlLevel);

        while ($data = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $domain = $data['HallId'];
            $oldLevel = $data['LevelId'];

            $this->map[$domain][$oldLevel] = 1;
        }

        $this->checkRemitAccountLevel();
        $this->clearRemitAccountLevelData();

        $this->printPerformance($startTime);
        $this->log('Finish.');
        $this->logger->popHandler()->close();
    }

    /**
     * 檢查remit_account_level未更新到的原因
     */
    private function checkRemitAccountLevel()
    {
        $this->log('Start check Remit Account Level...');

        $countSource = 0;
        $countDomain = 0;

        $domainSql = 'select id from user where id = ?';
        $sql = 'select ral.*, ra.domain ' .
            'from remit_account_level as ral ' .
            'left join remit_account as ra on ra.id = ral.remit_account_id';
        $statement = $this->conn->executeQuery($sql);

        while ($ret = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $remitAccountId = $ret['remit_account_id'];
            $oldLevel = $ret['level_id'];
            $domain = $ret['domain'];

            // 檢查研一那邊是否有層級資料
            if (!isset($this->map[$domain][$oldLevel])) {
                // old_level = 0為未分層, 會幫忙補上該廳的預設層級不需要刪除
                if ($oldLevel == 0) {
                    continue;
                }

                $this->log("[Rd1] Level not found, id:{$remitAccountId}, level_id:{$oldLevel}, domain:{$domain}");
                $countSource++;

                $this->ralSet[] = [
                    'remit_account_id' => $remitAccountId,
                    'level_id' => $oldLevel
                ];

                continue;
            }

            // 檢查帳號的廳是否為不存在
            $getDomain = $this->conn->fetchColumn($domainSql, [$domain]);

            if (!$getDomain) {
                $this->log("[RemitAccount] domain not found, id:{$remitAccountId}, level_id:{$oldLevel}, domain:{$domain}");
                $countDomain++;

                $this->ralSet[] = [
                    'remit_account_id' => $remitAccountId,
                    'level_id' => $oldLevel
                ];
            }
        }

        $this->log("層級找不到: {$countSource}, 入款帳號廳不存在: {$countDomain}");
        $this->log("Check Remit Account Level done.\n");
    }

    /**
     * 清除remit_account_level無效的資料
     */
    private function clearRemitAccountLevelData()
    {
        $this->log('Start clear Remit Account Level...');

        $count = 0;
        $sqlAll = [];

        foreach ($this->ralSet as $ral) {
            $remitAccountId = $ral['remit_account_id'];
            $levelId = $ral['level_id'];

            // 將刪除的資料做備份
            $sqlAll[] = "($remitAccountId, $levelId, 0)";
            $count += $this->conn->delete('remit_account_level', $ral);

        }

        if (count($sqlAll)) {
            $insertSql = 'insert into remit_account_level (remit_account_id, level_id, new_level) values ';
            $insertSql .= implode(', ', $sqlAll) . ';';
            $this->log($insertSql);
        }

        $this->log("已刪除{$count}筆");
        $this->log("Clear Remit Account Level done.\n");
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
        $this->logger = $this->getContainer()->get('durian.logger_manager')
            ->setUpLogger('clear_remit_account_level.log');
    }

    /**
     * 記錄error log
     *
     * @param string $msg
     */
    private function log($msg)
    {
        $this->logger->addInfo($msg);
    }
}
