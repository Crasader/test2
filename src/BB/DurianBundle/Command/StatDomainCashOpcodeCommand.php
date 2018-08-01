<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 統計廳的現金Opcode的金額、次數
 *
 * @author Linda 2015.01.23
 */
class StatDomainCashOpcodeCommand extends ContainerAwareCommand
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
     * @var boolean
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
     * 對應轉移的來源資料表
     *
     * @var array
     */
    private $sourceTable = [
        'stat_domain_cash_opcode' => 'stat_cash_opcode',
        'stat_domain_cash_opcode_hk' => 'stat_cash_opcode_hk'
    ];

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:stat-domain-cash-opcode')
            ->setDescription('統計會員現金Opocde金額、次數')
            ->addOption('start-date', null, InputOption::VALUE_REQUIRED, '統計日期起', null)
            ->addOption('end-date', null, InputOption::VALUE_REQUIRED, '統計日期迄', null)
            ->addOption('batch-size', null, InputOption::VALUE_OPTIONAL, '批次處理的數量', null)
            ->addOption('wait-sec', null, InputOption::VALUE_OPTIONAL, '等待秒數', null)
            ->addOption('slow', null, InputOption::VALUE_NONE, '分批刪除資料, 以防卡語法')
            ->addOption('recover', null, InputOption::VALUE_NONE, '補跑統計資料，不更改背景最後執行成功時間，避免自動觸發時統計區間錯誤')
            ->addOption('table-name', null, InputOption::VALUE_REQUIRED, '指定資料表')
            ->setHelp(<<<EOT
統計現金Opcode金額、次數
app/console durian:stat-domain-cash-opcode --start-date="2012/05/03" --end-date="2012/05/10"

指定轉移統計資料表，預設美東時間資料表
app/console durian:stat-domain-cash-opcode --start-date="2013/01/01" --end-date="2013/01/31" --table-name='stat_domain_cash_opcode'

補跑統計廳的現金Opcode金額、次數，不更改背景最後執行成功時間，避免自動觸發時統計區間錯誤
app/console durian:stat-domain-cash-opcode --start-date="2013/01/01" --end-date="2013/01/01" --recover
EOT
             );
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

        $this->tableName = 'stat_domain_cash_opcode';

        $table = [
            'stat_domain_cash_opcode',
            'stat_domain_cash_opcode_hk'
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
        $this->setOptions($input);
        $this->startTime = new \DateTime;

        $startDate = new \DateTime($input->getOption('start-date'));
        $endDate = new \DateTime($input->getOption('end-date'));

        //統計美東時間為前一天12:00:00~今天12:00:00
        if ($this->tableName == 'stat_domain_cash_opcode') {
            $startDate->setTime(12, 0, 0);
            $endDate->setTime(12, 0, 0);
        }

        //統計香港時間為前一天00:00:00~今天00:00:00
        if ($this->tableName == 'stat_domain_cash_opcode_hk') {
            $startDate->setTime(0, 0, 0);
            $endDate->setTime(0, 0, 0);
        }

        // 刪除原本資料
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

        $this->end();

        // 手動補跑時，不修改 background_process 資料，避免影響下次背景執行區間
        if (!$this->recover) {
            $bgMonitor->setMsgNum($msgNum);
            $bgMonitor->setLastEndTime($endDate);
            $bgMonitor->commandEnd();
        }
    }

    /**
     * 從stat_cash_opcode轉入stat_domain_cash_opcode
     *
     * @param \DateTime $startDate 起始時間
     * @return integer
     */
    private function convert(\DateTime $startDate)
    {
        $at = $startDate->format('Y-m-d H:i:s');

        $num = 0;
        $offset = 0;
        $limit = 10000;
        $total = $this->countStatCashOpcode($startDate);
        while ($offset < $total) {
            $stats = $this->getStatCashOpcode($startDate, $offset, $limit);
            $offset += $limit;

            $executeCount = 0;
            $statData = [];
            while ($stat = $stats->fetch()) {
                $statData[] = [
                    0,
                    $at,
                    $stat['user_id'],
                    $stat['currency'],
                    $stat['domain'],
                    $stat['opcode'],
                    $stat['amount'],
                    $stat['count']
                ];

                if (++$executeCount % $this->batchSize == 0) {
                    $num += $this->doMultiInsert($this->getMemberStatData($statData));
                    $statData = [];
                }
            }

            $num += $this->doMultiInsert($this->getMemberStatData($statData));
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

        $sql = "INSERT INTO $this->tableName (id,at,user_id,currency,domain,opcode,amount,`count`) VALUES ";

        // 測試環境要採用其他語法新增
        if ($this->isTest) {
            $multiSql = [];
            foreach ($values as $value) {
                $multiSql[] = $sql . $value;
            }

            $multiSql = implode(';', $multiSql) . ';';

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
     * 回傳統計資料(僅含會員)
     *
     * @param array $statData 統計資料
     * @return array
     */
    private function getMemberStatData($statData)
    {
        if (!$statData) {
            return [];
        }

        $userIds = array_column($statData, 2);

        $conn = $this->getEntityManager()->getConnection();
        $conn->connect('slave');

        $connShare = $this->getEntityManager('share')->getConnection();
        $connShare->connect('slave');

        $sql = 'SELECT id FROM user WHERE id IN (?) AND role = 1';
        $type = [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY];
        $users = $conn->executeQuery($sql, [$userIds], $type)->fetchAll();

        $sql = 'SELECT user_id as id FROM removed_user WHERE user_id IN (?) AND role = 1';
        $rmUsers = $connShare->executeQuery($sql, [$userIds], $type)->fetchAll();

        $members = array_merge($rmUsers, $users);
        if (!$members) {
            return [];
        }

        $memberIds = [];
        foreach ($members as $value) {
            $memberIds[$value['id']] = true;
        }

        $memberStatData = [];
        foreach ($statData as $stat) {
            if (!isset($memberIds[$stat[2]])) {
                continue;
            }

            $memberStatData[] = $stat;
        }

        return $memberStatData;
    }

    /**
     * 從stat_cash_opcode取得資料
     *
     * @param \DateTime $statDate 統計日期
     * @param integer   $offset
     * @param integer   $limit
     * @return \Doctrine\DBAL\Driver\Statement
     */
    private function getStatCashOpcode(\DateTime $statDate, $offset, $limit)
    {
        $start = $statDate->format('Y-m-d H:i:s');
        $end = clone $statDate;
        $end->add($this->addTime);
        $end = $end->format('Y-m-d H:i:s');

        $conn = $this->getEntityManager('his')->getConnection();
        $conn->connect('slave');

        $sql = 'SELECT * FROM ' . $this->sourceTable[$this->tableName] . ' WHERE at >= ? AND at < ? ORDER BY user_id, opcode LIMIT ?, ?';

        $params = [
            $start,
            $end,
            $offset,
            $limit
        ];

        $types = [
            \PDO::PARAM_STR,
            \PDO::PARAM_STR,
            \PDO::PARAM_INT,
            \PDO::PARAM_INT
        ];

        return $conn->executeQuery($sql, $params, $types);
    }

    /**
     * 取得stat_cash_opcode筆數
     *
     * @param \DateTime $statDate 統計日期
     * @return integer
     */
    private function countStatCashOpcode(\DateTime $statDate)
    {
        $start = $statDate->format('Y-m-d H:i:s');
        $end = clone $statDate;
        $end->add($this->addTime);
        $end = $end->format('Y-m-d H:i:s');

        $conn = $this->getEntityManager('his')->getConnection();
        $conn->connect('slave');

        $countSql = 'SELECT count(*) FROM ' . $this->sourceTable[$this->tableName] . ' WHERE at >= ? AND at < ?';

        $params = [
            $start,
            $end
        ];

        return $conn->fetchColumn($countSql, $params);
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
