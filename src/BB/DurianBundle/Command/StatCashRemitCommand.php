<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\StatOpcode;

/**
 * 統計現金匯款優惠金額、次數
 *
 * @author Sweet 2014.11.18
 */
class StatCashRemitCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * 程式開始執行時間
     *
     * @var \DateTime
     */
    private $startTime;

    /**
     * 每次下語法刪掉的筆數
     *
     * @var integer
     */
    private $batchSize;

    /**
     * 等待時間
     *
     * @var integer
     */
    private $waitTime;

    /**
     * 增加時間
     *
     * @var integer
     */
    private $addTime;

    /**
     * 若設為true, 則會分批刪除資料
     *
     * @var bool
     */
    private $slowly = false;

    /**
     * 若設為true, 則不更改背景最後執行成功時間，避免自動觸發時統計區間錯誤
     *
     * @var bool
     */
    private $recover = false;

    /**
     * 是否為測試環境
     *
     * @var boolean
     */
    private $isTest;

    /**
     * 匯款優惠統計資料
     *
     * @var array
     */
    private $remits = [];

    /**
     * 最後一筆資料
     *
     * @var array
     */
    private $lastData = [];

    /**
     * 匯款優惠對應表
     *
     * @var array
     */
    private $remitOpcodeMap = [
        1012 => 'offer_remit',
        1038 => 'offer_company_remit'
    ];

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:stat-cash-remit')
            ->setDescription('統計現金匯款優惠金額、次數')
            ->addOption('start-date', null, InputOption::VALUE_REQUIRED, '統計日期起', null)
            ->addOption('end-date', null, InputOption::VALUE_REQUIRED, '統計日期迄', null)
            ->addOption('batch-size', null, InputOption::VALUE_OPTIONAL, '批次處理的數量', null)
            ->addOption('wait-sec', null, InputOption::VALUE_OPTIONAL, '等待秒數', null)
            ->addOption('slow', null, InputOption::VALUE_NONE, '分批刪除資料, 以防卡語法')
            ->addOption('recover', null, InputOption::VALUE_NONE, '補跑統計資料，不更改背景最後執行成功時間，避免自動觸發時統計區間錯誤')
            ->setHelp(<<<EOT
統計現金匯款優惠金額、次數
app/console durian:stat-cash-remit --start-date="2013/01/01" --end-date="2013/01/31"

補跑統計現金匯款優惠金額、次數，不更改背景最後執行成功時間，避免自動觸發時統計區間錯誤
app/console durian:stat-cash-remit --start-date="2013/01/01" --end-date="2013/01/01" --recover
EOT
             );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setOptions($input);

        // 手動補跑時，不修改 background_process 資料，避免影響下次背景執行區間
        if (!$this->recover) {
            $bgMonitor = $this->getContainer()->get('durian.monitor.background');
            $bgMonitor->commandStart('stat-cash-remit');
        }

        $this->output = $output;
        $this->start();

        $startDate = new \DateTime($input->getOption('start-date'));
        $startDate->setTime(12, 0, 0);
        $endDate = new \DateTime($input->getOption('end-date'));
        $endDate->setTime(12, 0, 0);

        //刪除原本資料
        $this->removeData($startDate, $endDate);

        $msgNum = 0;
        while ($startDate <= $endDate) {
            $msgNum += $this->sumStat($startDate);
            $startDate->add($this->addTime);
            usleep($this->waitTime);
        }

        if ($msgNum) {
            $this->checkLastData();
        }

        $this->end();

        // 手動補跑時，不修改 background_process 資料，避免影響下次背景執行區間
        if (!$this->recover) {
            $bgMonitor->setMsgNum($msgNum);
            $bgMonitor->setLastEndTime($endDate);
            $bgMonitor->commandEnd();
        }
    }

    /**
     * 開始執行、紀錄開始時間
     */
    private function start()
    {
        $this->startTime = new \DateTime;
    }

    /**
     * 程式結束顯示處理時間、記憶體
     */
    private function end()
    {
        $endTime = new \DateTime;
        $costTime = $endTime->diff($this->startTime, true);
        $this->output->writeln('Execute time: ' . $costTime->format('%H:%I:%S'));

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);
        $this->output->writeln("Memory MAX use: $usage M");
    }

    /**
     * 設定參數
     *
     * @param InputInterface $input
     */
    private function setOptions(InputInterface $input)
    {
        $this->slowly = false;

        if ($input->getOption('slow')) {
            $this->slowly = true;
        }

        if ($input->getOption('recover')) {
            $this->recover = true;
        }

        $this->batchSize = 500;

        if ($input->getOption('batch-size')) {
            $this->batchSize = $input->getOption('batch-size');
        }

        $this->waitTime = 500000;

        if ($input->getOption('wait-sec')) {
            $this->waitTime = $input->getOption('wait-sec') * 1000000;
        }

        $this->addTime = new \DateInterval('P1D');

        $this->isTest = false;

        if ($this->getContainer()->getParameter('kernel.environment') == 'test') {
            $this->isTest = true;
        }
    }

    /**
     * 彙總統計資料到會員統計匯款優惠資料表
     *
     * @param \DateTime $startDate
     * @return integer $num
     */
    private function convert(\DateTime $startDate)
    {
        $conn = $this->getEntityManager('his')->getConnection();
        $conn->connect('slave');

        $at = $startDate->format('Y-m-d H:i:s');

        $executeCount = 0;
        $num = 0;
        $inserts = [];

        foreach ($this->remits as $userId => $remit) {
            if (++$executeCount % $this->batchSize == 0) {
                $num += $this->doMultiInsert($inserts);
                $inserts = [];
            }

            $inserts[] = [
                0,
                $at,
                $userId,
                $remit['currency'],
                $remit['domain'],
                $remit['parent_id'],
                $remit['offer_remit_amount'],
                $remit['offer_remit_count'],
                $remit['offer_company_remit_amount'],
                $remit['offer_company_remit_count'],
                $remit['remit_amount'],
                $remit['remit_count']
            ];
        }

        if ($inserts) {
            $num += $this->doMultiInsert($inserts);
        }

        return $num;
    }

    /**
     * 批次新增
     *
     * @param array $inserts 要新增的資料
     * @return integer
     */
    private function doMultiInsert(Array $inserts)
    {
        if (!$inserts) {
            return 0;
        }

        $conn = $this->getEntityManager('his')->getConnection();

        $values = [];
        foreach ($inserts as $insert) {
            $values[] = sprintf("('%s')", implode("','", $insert));
        }

        $sql = 'INSERT INTO stat_cash_remit (id,at,user_id,currency,domain,parent_id,' .
            'offer_remit_amount,offer_remit_count,offer_company_remit_amount,' .
            'offer_company_remit_count,remit_amount,remit_count) VALUES ';

        // 測試環境要採用其他語法新增
        if ($this->isTest) {
            $multiSql = [];
            foreach ($values as $value) {
                $multiSql[] = $sql . $value;
            }

            $multiSql = implode(';', $multiSql);

            // sqlite的id是主鍵，insert不用指定id值
            $multiSql = str_replace('(id,', '(', $multiSql);
            $multiSql = str_replace("('0',", '(', $multiSql);

            return $conn->executeUpdate($multiSql);
        }

        $sql .= implode(',', $values);
        $ret = $conn->executeUpdate($sql);

        usleep($this->waitTime);

        return $ret;
    }

    /**
     * 刪除原本資料
     *
     * @param \DateTime $startDate 開始日期
     * @param \DateTime $endDate   結束日期
     */
    private function removeData(\DateTime $startDate, \DateTime $endDate)
    {
        $conn = $this->getEntityManager('his')->getConnection();

        $start = $startDate->format('Y-m-d H:i:s');
        $end = $endDate->format('Y-m-d H:i:s');
        $params = [
            $start,
            $end
        ];

        $sql = 'SELECT COUNT(id) FROM stat_cash_remit WHERE at >= ? AND at <= ?';
        $count = $conn->fetchColumn($sql, $params);

        if ($count == 0) {
            return;
        }

        // 直接刪除
        if (!$this->slowly) {
            $sql = 'DELETE FROM stat_cash_remit WHERE at >= ? AND at <= ?';
            $conn->executeUpdate($sql, $params);

            return;
        }

        // 慢慢刪除
        $sql = sprintf(
            'DELETE FROM stat_cash_remit WHERE at >= ? AND at <= ? LIMIT %d',
            $this->batchSize
        );

        while ($count > 0) {
            $conn->executeUpdate($sql, $params);
            $count -= $this->batchSize;
            usleep($this->waitTime);
        }
    }

    /**
     * 從中介表統計匯款優惠金額、次數
     *
     * @param \DateTime $statDate 統計日期
     * @return integer $num
     */
    private function sumStat(\DateTime $statDate)
    {
        $at = $statDate->format('Y-m-d H:i:s');

        $conn = $this->getEntityManager('his')->getConnection();
        $conn->connect('slave');

        $countSql = 'SELECT count(1) FROM stat_cash_opcode WHERE at = ? AND opcode IN (?)';
        $sql = 'SELECT * FROM stat_cash_opcode WHERE at = ? AND opcode IN (?)  '.
            'ORDER BY user_id, opcode LIMIT ?, ?';

        $params = [
            $at,
            StatOpcode::$cashRemitOpcode
        ];

        $types = [
            \PDO::PARAM_STR,
            \Doctrine\DBAL\Connection::PARAM_INT_ARRAY
        ];

        $offset = 0;
        $limit = 50000;
        $total = $conn->executeQuery($countSql, $params, $types)->fetchColumn();

        $this->remits = [];
        $userCount = 0;
        $lastUserId = 0;
        $num = 0;

        while ($offset < $total) {
            $params = [
                $at,
                StatOpcode::$cashRemitOpcode,
                $offset,
                $limit
            ];

            $types = [
                \PDO::PARAM_STR,
                \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
                \PDO::PARAM_INT,
                \PDO::PARAM_INT
            ];

            $stats = $conn->executeQuery($sql, $params, $types);
            $offset += $limit;

            while ($stat = $stats->fetch()) {
                $userId = $stat['user_id'];

                if ($userId != $lastUserId) {
                    $userCount++;
                }

                if ($userCount % 1000 == 0 && $userId != $lastUserId) {
                    $num += $this->convert($statDate);
                    $this->remits = [];
                }

                $opcode = $stat['opcode'];
                $column = $this->remitOpcodeMap[$opcode];

                if (!isset($this->remits[$userId])) {
                    $this->init($stat);
                }

                $this->remits[$userId][$column . '_amount'] += $stat['amount'];
                $this->remits[$userId][$column . '_count'] += $stat['count'];
                $this->remits[$userId]['remit_amount'] += $stat['amount'];
                $this->remits[$userId]['remit_count'] += $stat['count'];

                $lastUserId = $stat['user_id'];

                $this->lastData['at'] = $at;
                $this->lastData['user_id'] = $stat['user_id'];
            }
        }

        if ($this->remits) {
            $num += $this->convert($statDate);
            $this->remits = [];
        }

        return $num;
    }

    /**
     * 初始化統計資料
     *
     * @param array $stat 要初始化的資料
     */
    private function init($stat)
    {
        $userId = $stat['user_id'];

        $this->remits[$userId]['currency'] = $stat['currency'];
        $this->remits[$userId]['domain'] = $stat['domain'];
        $this->remits[$userId]['parent_id'] = $stat['parent_id'];
        $this->remits[$userId]['remit_amount'] = 0;
        $this->remits[$userId]['remit_count'] = 0;

        foreach ($this->remitOpcodeMap as $column) {
            $this->remits[$userId][$column . '_amount'] = 0;
            $this->remits[$userId][$column . '_count'] = 0;
        }
    }

    /**
     * 確認最後一筆資料寫入slave，避免slave落後造成統計資料錯誤
     */
    private function checkLastData()
    {
        $conn = $this->getEntityManager('his')->getConnection();

        $params = [
            $this->lastData['at'],
            $this->lastData['user_id']
        ];

        $sql = 'SELECT COUNT(id) FROM stat_cash_remit WHERE at = ? AND user_id = ?';

        // 還沒寫入就一直撈
        while (!$conn->fetchColumn($sql, $params)) {
            sleep(5);
        }
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
