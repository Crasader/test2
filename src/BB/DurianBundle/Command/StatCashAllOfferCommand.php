<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 統計現金全部優惠金額、次數
 *
 * @author Sweet 2014.11.18
 */
class StatCashAllOfferCommand extends ContainerAwareCommand
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
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:stat-cash-all-offer')
            ->setDescription('統計現金全部優惠金額、次數')
            ->addOption('start-date', null, InputOption::VALUE_REQUIRED, '統計日期起', null)
            ->addOption('end-date', null, InputOption::VALUE_REQUIRED, '統計日期迄', null)
            ->addOption('batch-size', null, InputOption::VALUE_OPTIONAL, '批次處理的數量', null)
            ->addOption('wait-sec', null, InputOption::VALUE_OPTIONAL, '等待秒數', null)
            ->addOption('slow', null, InputOption::VALUE_NONE, '分批刪除資料, 以防卡語法')
            ->addOption('recover', null, InputOption::VALUE_NONE, '補跑統計資料，不更改背景最後執行成功時間，避免自動觸發時統計區間錯誤')
            ->setHelp(<<<EOT
統計現金全部優惠金額、次數
app/console durian:stat-cash-all-offer --start-date="2013/01/01" --end-date="2013/01/31"

補跑統計現金全部優惠金額、次數，不更改背景最後執行成功時間，避免自動觸發時統計區間錯誤
app/console durian:stat-cash-all-offer --start-date="2013/01/01" --end-date="2013/01/31" --recover
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
            $bgMonitor->commandStart('stat-cash-all-offer');
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
            $msgNum += $this->convert($startDate);
            $startDate->add($this->addTime);
            usleep($this->waitTime);
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
     * 從優惠表、返點表、匯款優惠表彙總到會員統計全部優惠資料表
     *
     * @param \DateTime $startDate
     * @return integer $num
     */
    private function convert(\DateTime $startDate)
    {
        $conn = $this->getEntityManager('his')->getConnection();
        $conn->connect('slave');
        $at = $startDate->format('Y-m-d H:i:s');

        $offset = 0;
        $limit = 500000;
        $total = $this->getSumStatNum($startDate);
        $executeCount = 0;
        $num = 0;
        $inserts = [];

        while ($offset < $total) {
            $stats = $this->getSumStat($startDate, $offset, $limit);
            $offset += $limit;

            while ($stat = $stats->fetch()) {
                if (++$executeCount % $this->batchSize == 0) {
                $num += $this->doMultiInsert($inserts);
                    $inserts = [];
                }

                $inserts[] = [
                    0,
                    $at,
                    $stat['user_id'],
                    $stat['currency'],
                    $stat['domain'],
                    $stat['parent_id'],
                    $stat['offer_rebate_remit_amount'],
                    $stat['offer_rebate_remit_count']
                ];
            }
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

        $sql = 'INSERT INTO stat_cash_all_offer (id,at,user_id,currency,domain,parent_id,' .
            'offer_rebate_remit_amount,offer_rebate_remit_count) VALUES ';

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

        $sql = 'SELECT COUNT(id) FROM stat_cash_all_offer WHERE at >= ? AND at <= ?';
        $count = $conn->fetchColumn($sql, $params);

        if ($count == 0) {
            return;
        }

        // 直接刪除
        if (!$this->slowly) {
            $sql = 'DELETE FROM stat_cash_all_offer WHERE at >= ? AND at <= ?';
            $conn->executeUpdate($sql, $params);

            return;
        }

        // 慢慢刪除
        $sql = sprintf(
            'DELETE FROM stat_cash_all_offer WHERE at >= ? AND at <= ? LIMIT %d',
            $this->batchSize
        );

        while ($count > 0) {
            $conn->executeUpdate($sql, $params);
            $count -= $this->batchSize;
            usleep($this->waitTime);
        }
    }

    /**
     * 從優惠表、返點表、匯款優惠表統計全部優惠金額、次數總筆數
     *
     * @param \DateTime $statDate 統計日期
     * @return integer
     */
    private function getSumStatNum(\DateTime $statDate)
    {
        $at = $statDate->format('Y-m-d H:i:s');

        $conn = $this->getEntityManager('his')->getConnection();
        $conn->connect('slave');

        $sql = 'SELECT count(user_id) FROM (' .
            'SELECT user_id FROM stat_cash_offer sco WHERE at = ? UNION ' .
            'SELECT user_id FROM stat_cash_rebate srb WHERE at = ? UNION ' .
            'SELECT user_id FROM stat_cash_remit srm WHERE at = ?) AS ao ';

        $params = [
            $at,
            $at,
            $at
        ];

        $types = [
            \PDO::PARAM_STR,
            \PDO::PARAM_STR,
            \PDO::PARAM_STR
        ];

        return $conn->executeQuery($sql, $params, $types)->fetchColumn();
    }

    /**
     * 從優惠表、返點表、匯款優惠表統計全部優惠金額、次數
     *
     * @param \DateTime $statDate 統計日期
     * @param integer   $offset   資料開頭
     * @param integer   $limit    資料筆數
     * @return array
     */
    private function getSumStat(\DateTime $statDate, $offset, $limit)
    {
        $at = $statDate->format('Y-m-d H:i:s');

        $conn = $this->getEntityManager('his')->getConnection();
        $conn->connect('slave');

        $sql = 'SELECT user_id,currency,domain,parent_id,' .
            'SUM(am) AS offer_rebate_remit_amount,SUM(co) AS offer_rebate_remit_count FROM (' .
            'SELECT user_id, currency, domain, parent_id, offer_amount AS am, offer_count AS co ' .
            'FROM stat_cash_offer sco WHERE at = ? UNION ALL ' .
            'SELECT user_id, currency, domain, parent_id, rebate_amount AS am, rebate_count AS co ' .
            'FROM stat_cash_rebate srb WHERE at = ? UNION ALL ' .
            'SELECT user_id, currency, domain, parent_id, remit_amount AS am, remit_count AS co ' .
            'FROM stat_cash_remit srm WHERE at = ?) AS ao ' .
            'GROUP BY ao.user_id ' .
            'ORDER BY ao.user_id ' .
            'LIMIT ?, ?';

        $params = [
            $at,
            $at,
            $at,
            $offset,
            $limit
        ];

        $types = [
            \PDO::PARAM_STR,
            \PDO::PARAM_STR,
            \PDO::PARAM_STR,
            \PDO::PARAM_INT,
            \PDO::PARAM_INT
        ];

        return $conn->executeQuery($sql, $params, $types);
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
