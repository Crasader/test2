<?php
namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 轉移操作紀錄到infobright
 *
 * @author Sweet 2014.10.23
 */
class MigrateLogOperationCommand extends ContainerAwareCommand
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
     * mysql連線
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * infobright連線
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $hisConn;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:migrate-log-operation')
            ->setDescription('轉移操作紀錄到infobright')
            ->addOption('end-date', null, InputOption::VALUE_REQUIRED, '轉移日期迄', null)
            ->setHelp(<<<EOT
轉移指定時間以前(包含指定時間)的操作紀錄至infobright
app/console durian:migrate-log-operation --end-date="2014/10/01"
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart('migrate-log-operation');

        $startAt = new \DateTime;

        $this->input = $input;
        $this->output = $output;

        $this->conn = $this->getEntityManager('share')->getConnection();
        $this->hisConn = $this->getEntityManager('his')->getConnection();

        $endDate = $this->input->getOption('end-date');

        if (!$endDate) {
            throw new \Exception('需指定結束日期');
        }

        $executeCount = $this->doMigrate($endDate);
        $this->output->write("Insert total rows: $executeCount", true);

        $this->end($startAt);

        $bgMonitor->setMsgNum($executeCount);
        $bgMonitor->commandEnd();
    }

    /**
     * 轉移資料
     *
     * @param string $endDate 結束日期
     * @return integer
     */
    private function doMigrate($endDate)
    {
        $executeCount = 0;

        $sql = 'SELECT MAX(id) FROM log_operation';
        $lastId = $this->hisConn->fetchColumn($sql);

        $endDate = (new \DateTime($endDate))->add(new \DateInterval('P1D'));
        $endDate = $endDate->format('Y-m-d H:i:s');

        // 如果infobright無資料
        if (is_null($lastId)) {
            $lastId = 0;
        }

        while (true) {
            $sql = 'SELECT id, table_name, major_key, uri, method, at, server_name, client_ip, message, session_id ' .
                   'FROM log_operation ' .
                   'WHERE at < :end AND id > :lastId ' .
                   'ORDER BY id LIMIT 1000';

            $params = [
                'end' => $endDate,
                'lastId' => $lastId
            ];

            $results = $this->conn->fetchAll($sql, $params);

            if (!$results) {
                break;
            }

            $bottom = end($results);
            $lastId = $bottom['id'];

            $values = [];
            foreach ($results as $result) {
                $result['message'] = addslashes($result['message']);
                $values[] = '("' . implode('","', $result) . '")';
                $executeCount++;
            }

            $sql = 'INSERT INTO log_operation (id,table_name,major_key,uri,' .
                   'method,at,server_name,client_ip,message, session_id) VALUES ';

            $sql .= implode(',', $values);
            $this->hisConn->executeUpdate($sql);

            $this->output->write("$executeCount rows have been inserted.", true);
            usleep(500000);
        }

        return $executeCount;
    }

    /**
     * 程式結束顯示處理時間、記憶體
     *
     * @param \DateTime $startAt 開始時間
     */
    private function end($startAt)
    {
        $endTime = new \DateTime;
        $costTime = $endTime->diff($startAt, true);
        $this->output->write('Execute time: ' . $costTime->format('%H:%I:%S'), true);

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);
        $this->output->write("Memory MAX use: $usage M", true);
    }

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name EntityManager名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }
}
