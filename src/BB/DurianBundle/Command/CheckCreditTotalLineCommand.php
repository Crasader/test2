<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class CheckCreditTotalLineCommand extends ContainerAwareCommand
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Array
     */
    private $sqlPool;

    /**
     * prepare的參數
     *
     * @var Array
     */
    private $params;

    /**
     * @var Boolean
     */
    private $execQuery;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:check-credit-total-line')
            ->setDescription('檢查信用額度下層額度上限總合是否與上層total_line相同')
            ->addOption('exec-query', null, InputOption::VALUE_NONE, '直接下UPDATE語法，而非輸出語法')
            ->setHelp(<<<EOT
查信用額度下層額度上限總合是否與上層total_line相同，
並且輸出資料差異的csv檔及update的語法
若下了--exec-query 的指令，則不輸出語法，改為直接update資料庫的資料
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        $this->execQuery = $this->input->getOption('exec-query');
        $this->sqlPool = array();

        $curDate = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));

        $sql = "SELECT id, user_id, total_line, group_num FROM credit ".
               "WHERE total_line > 0";
        $state = $this->getConnection()->query($sql);
        $creditRepo = $this->getEntityManager()->getRepository('BB\DurianBundle\Entity\Credit');

        $i = 0;
        $errCount = 0;
        $exCount = 0;

        while ($row = $state->fetch()) {
            $totalEnable = $creditRepo->getTotalEnable($row['user_id'], $row['group_num']);
            $totalDisable = $creditRepo->getTotalDisable($row['user_id'], $row['group_num']);
            $childrenTotalLine = $totalEnable + $totalDisable;

            if ($row['total_line'] != $childrenTotalLine) {
                //若未開檔案則開檔案
                $file = 'totalline_diff_' . $curDate->format('Ymdhis') . '.csv';
                $ouputCsv = fopen($file, 'a+');
                fwrite($ouputCsv, "user_id, group_num, credit_id, total_line(before), total_line(after)\n");

                //將信用額度資料輸出
                $pieces = array(
                    $row['user_id'],
                    $row['group_num'],
                    $row['id'],
                    $row['total_line'],
                    $childrenTotalLine,
                );
                $str = implode(',', $pieces) . PHP_EOL;
                fwrite($ouputCsv, $str);

                $this->sqlPool[] = "UPDATE credit SET total_line = ? WHERE id = ?;";
                $this->params[] = array(
                    $childrenTotalLine,
                    $row['id']
                );
                $errCount++;
            }

            $i++;
            $exCount++;
            if ($i >= 50) {
                $i = 0;
                sleep(1);
            }
        }

        $this->accessUpdateSql($curDate);
        $this->showTimeInfo($curDate);
    }

    /**
     * @param \DateTime $curDate
     */
    private function accessUpdateSql($curDate)
    {
        if (empty($this->sqlPool)) {
            return;
        }

        $params = array();
        foreach ($this->params as $param) {
            $params[] = $param[0];
            $params[] = $param[1];
        }

        if (!$this->execQuery) {
            $sqlLog = '';
            foreach ($this->sqlPool as $i => $sql) {
                $format = str_replace('?', "'%s'", $sql);
                $newSql = vsprintf($format, $this->params[$i]);
                $sqlLog .= $newSql . PHP_EOL;
            }

            $file = 'update_sql_' . $curDate->format('Ymdhis') . '.sql';
            $ouputSql = fopen($file, 'w');
            fwrite($ouputSql, $sqlLog);
        } else {
            $this->getEntityManager()->getConnection()->executeUpdate(implode($this->sqlPool), $params);
        }
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        if ($this->em) {
            return $this->em;
        }

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        return $this->em;
    }

    /**
     * 回傳DB連線
     *
     * @return \Doctrine\DBAL\Connection
     */
    private function getConnection()
    {
        if ($this->conn) {
            return $this->conn;
        }

        $this->conn = $this->getContainer()->get('doctrine.dbal.default_connection');
        return $this->conn;
    }

    /**
     * 顯示此命令跑的時間點資訊
     *
     * @param \DateTime $beginDate
     */
    private function showTimeInfo($beginDate)
    {
        $endDate  = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $beginStr = $beginDate->format(\DateTime::ISO8601);
        $endStr   = $endDate->format(\DateTime::ISO8601);

        $this->output->write("{$beginStr} : CheckCreditTotalLineCommand begin...", true);
        $this->output->write("{$endStr} : CheckCreditTotalLineCommand finish...", true);
    }
}
