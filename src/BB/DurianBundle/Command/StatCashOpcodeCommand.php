<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\StatOpcode;

/**
 * 統計現金Opcode的金額、次數
 *
 * @author Chuck 2014.10.02
 */
class StatCashOpcodeCommand extends ContainerAwareCommand
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
     * 指定資料表
     *
     * @var string
     */
    private $tableName;

    /**
     * 最後一筆資料
     *
     * @var array
     */
    private $lastData = [];

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:stat-cash-opcode')
            ->setDescription('統計現金Opocde金額、次數')
            ->addOption('start-date', null, InputOption::VALUE_REQUIRED, '統計日期起', null)
            ->addOption('end-date', null, InputOption::VALUE_REQUIRED, '統計日期迄', null)
            ->addOption('batch-size', null, InputOption::VALUE_OPTIONAL, '批次處理的數量', null)
            ->addOption('wait-sec', null, InputOption::VALUE_OPTIONAL, '等待秒數', null)
            ->addOption('slow', null, InputOption::VALUE_NONE, '分批刪除資料, 以防卡語法')
            ->addOption('recover', null, InputOption::VALUE_NONE, '補跑統計資料，不更改背景最後執行成功時間，避免自動觸發時統計區間錯誤')
            ->addOption('table-name', null, InputOption::VALUE_REQUIRED, '指定資料表')
            ->setHelp(<<<EOT
統計現金Opcode金額、次數
app/console durian:stat-cash-opcode --start-date="2013/01/01" --end-date="2013/01/31"

指定轉移統計資料表，預設美東時間資料表
app/console durian:stat-cash-opcode --start-date="2013/01/01" --end-date="2013/01/31" --table-name='stat_cash_opcode'

補跑統計現金Opcode金額、次數，不更改背景最後執行成功時間，避免自動觸發時統計區間錯誤
app/console durian:stat-cash-opcode --start-date="2013/01/01" --end-date="2013/01/01" --recover
EOT
             );
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

        $this->tableName = 'stat_cash_opcode';

        $table = [
            'stat_cash_opcode',
            'stat_cash_opcode_hk'
        ];

        if ($input->getOption('table-name') && in_array($input->getOption('table-name'), $table)) {
            $this->tableName = $input->getOption('table-name');
        }
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setOptions($input);
        $commandName = str_replace('_', '-', $this->tableName);

        // 手動補跑時，不修改 background_process 資料，避免影響下次背景執行區間
        if (!$this->recover) {
            $bgMonitor = $this->getContainer()->get('durian.monitor.background');
            $bgMonitor->commandStart($commandName);
        }

        $this->output = $output;
        $this->start();

        $startDate = new \DateTime($input->getOption('start-date'));
        $endDate = new \DateTime($input->getOption('end-date'));

        //統計美東時間為前一天12:00:00~今天12:00:00
        if ($this->tableName == 'stat_cash_opcode') {
            $startDate->setTime(12, 0, 0);
            $endDate->setTime(12, 0, 0);
        }

        //統計香港時間為前一天00:00:00~今天00:00:00
        if ($this->tableName == 'stat_cash_opcode_hk') {
            $startDate->setTime(0, 0, 0);
            $endDate->setTime(0, 0, 0);
        }

        //刪除原本資料
        $this->removeData($startDate, $endDate);

        $msgNum = 0;

        while ($startDate <= $endDate) {
            $msgNum += $this->convert($startDate);
            $startDate->add($this->addTime);

            // 測試時不暫停
            if (!$this->isTest) {
                usleep($this->waitTime);
            }
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
     * 從明細資料匯總到中介表
     *
     * @param \DateTime $startDate
     * @return integer $num
     */
    private function convert(\DateTime $startDate)
    {
        $at = $startDate->format('Y-m-d H:i:s');

        $num = 0;
        $offset = 0;
        $limit = 100000;
        $total = $this->countCashEntry($startDate);
        while ($offset < $total) {
            $stats = $this->getSumStat($startDate, $offset, $limit);
            $offset += $limit;

            $executeCount = 0;
            $statData = [];
            while ($stat = $stats->fetch()) {
                $statData[] = [
                    0,
                    $at,
                    $stat['user_id'],
                    $stat['currency'],
                    $stat['opcode'],
                    $stat['amount'],
                    $stat['entry_count']
                ];

                if (++$executeCount % $this->batchSize == 0) {
                    $num += $this->doMultiInsert($this->appendUserData($statData));
                    $statData = [];
                }

                $this->lastData['at'] = $at;
                $this->lastData['user_id'] = $stat['user_id'];
                $this->lastData['opcode'] = $stat['opcode'];
            }

            $num += $this->doMultiInsert($this->appendUserData($statData));
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
            $values[] = "('" . implode("','", $insert) . "')";
        }

        $sql = "INSERT INTO $this->tableName (id,at,user_id,currency,domain,parent_id," .
            'opcode,amount,count) VALUES ';

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

        $sql = "SELECT COUNT(id) FROM $this->tableName WHERE at >= ? AND at <= ?";
        $count = $conn->fetchColumn($sql, $params);

        if ($count == 0) {
            return;
        }

        // 直接刪除
        if (!$this->slowly) {
            $sql = "DELETE FROM $this->tableName WHERE at >= ? AND at <= ?";
            $conn->executeUpdate($sql, $params);

            return;
        }

        // 慢慢刪除
        $sql = sprintf(
            "DELETE FROM $this->tableName WHERE at >= ? AND at <= ? LIMIT %d",
            $this->batchSize
        );

        while ($count > 0) {
            $conn->executeUpdate($sql, $params);
            $count -= $this->batchSize;

            if (!$this->isTest) {
                usleep($this->waitTime);
            }
        }
    }

    /**
     * 回傳附加doamin/parent_id的統計資料
     *
     * @param array $statData 統計資料
     * @return array
     */
    private function appendUserData($statData)
    {
        if (!$statData) {
            return [];
        }

        $userIds = array_column($statData, 2);

        $conn = $this->getEntityManager()->getConnection();
        $conn->connect('slave');

        $connShare = $this->getEntityManager('share')->getConnection();
        $connShare->connect('slave');

        $sql = 'SELECT id, domain, parent_id FROM user WHERE id IN (?)';
        $type = [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY];
        $users = $conn->executeQuery($sql, [$userIds], $type)->fetchAll();

        $sql = 'SELECT user_id as id, domain, parent_id FROM removed_user WHERE user_id IN (?)';
        $rmUsers = $connShare->executeQuery($sql, [$userIds], $type)->fetchAll();

        $users = array_merge($rmUsers, $users);
        if (!$users) {
            return [];
        }

        $userData = [];
        foreach ($users as $value) {
            if (is_null($value['parent_id'])) {
                $value['parent_id'] = 0;
            }

            $userData[$value['id']] = [
                $value['domain'],
                $value['parent_id']
            ];
        }

        $newStatData = [];
        foreach ($statData as $stat) {
            if (!isset($userData[$stat[2]])) {
                continue;
            }

            array_splice($stat, 4, 0, $userData[$stat[2]]);
            $newStatData[] = $stat;
        }

        return $newStatData;
    }

    /**
     * 從明細統計各opcode的金額、次數
     *
     * @param \DateTime $statDate 統計日期
     * @param integer   $offset
     * @param integer   $limit
     * @return array
     */
    private function getSumStat(\DateTime $statDate, $offset, $limit)
    {
        $start = $statDate->format('YmdHis');
        $end = clone $statDate;
        $end->add($this->addTime);
        $end = $end->format('YmdHis');

        $conn = $this->getEntityManager('his')->getConnection();
        $conn->connect('slave');

        $sql = 'SELECT user_id, currency, opcode, SUM(amount) as amount, COUNT(1) AS entry_count' .
            ' FROM cash_entry WHERE at >= ? AND at < ?' .
            ' AND opcode IN (?)' .
            ' GROUP BY user_id, opcode' .
            ' ORDER BY user_id, opcode LIMIT ?, ?';

        $params = [
            $start,
            $end,
            StatOpcode::$all,
            $offset,
            $limit
        ];

        $types = [
            \PDO::PARAM_INT,
            \PDO::PARAM_INT,
            \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
            \PDO::PARAM_INT,
            \PDO::PARAM_INT
        ];

        return $conn->executeQuery($sql, $params, $types);
    }

    /**
     * 取得cash_entry筆數
     *
     * @param \DateTime $statDate 統計日期
     * @return integer
     */
    private function countCashEntry(\DateTime $statDate)
    {
        $start = $statDate->format('YmdHis');
        $end = clone $statDate;
        $end->add($this->addTime);
        $end = $end->format('YmdHis');

        $conn = $this->getEntityManager('his')->getConnection();
        $conn->connect('slave');

        $countSql = 'SELECT count(1) FROM (' .
            ' SELECT 1 FROM cash_entry WHERE at >= ? AND at < ?'.
            ' AND opcode IN (?)' .
            ' GROUP BY user_id, opcode) a';

        $params = [
            $start,
            $end,
            StatOpcode::$all
        ];

        $types = [
            \PDO::PARAM_INT,
            \PDO::PARAM_INT,
            \Doctrine\DBAL\Connection::PARAM_INT_ARRAY
        ];

        return $conn->executeQuery($countSql, $params, $types)->fetchColumn();
    }

    /**
     * 確認最後一筆資料寫入slave，避免slave落後造成統計資料錯誤
     */
    private function checkLastData()
    {
        $conn = $this->getEntityManager('his')->getConnection();

        $params = [
            $this->lastData['at'],
            $this->lastData['user_id'],
            $this->lastData['opcode']
        ];

        $sql = "SELECT COUNT(id) FROM $this->tableName WHERE at = ? AND user_id = ? " .
            'AND opcode = ?';

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
