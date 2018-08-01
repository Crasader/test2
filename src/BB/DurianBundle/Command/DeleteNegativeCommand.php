<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 清除餘額已大於零的負數資料
 *
 * @author Ruby 2017.02.08
 */
class DeleteNegativeCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * 乾跑
     *
     * @var boolean
     */
    private $dryRun;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:delete-negative')
            ->setDescription('清除餘額已大於零的負數資料')
            ->addOption('table', 't', InputOption::VALUE_REQUIRED, '指定資料表', 'cash')
            ->addOption('start-user-id', 'u', InputOption::VALUE_REQUIRED, '開始執行的使用者編號', 0)
            ->addOption('max-user', 'm', InputOption::VALUE_OPTIONAL, '每次最多處理幾個使用者', 1000)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '乾跑，不寫入資料', null)
            ->setHelp(<<<EOT
清除餘額已大於零的負數資料
app/console durian:delete-negative -t cash_fake -u 123 --max-user 50

清除餘額已大於零的負數資料，不寫資料
app/console durian:delete-negative -u 123 --dry-run

EOT
             );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $table = $input->getOption('table');

        if (!in_array($table, ['cash', 'cash_fake'])) {
            throw new \InvalidArgumentException('Invalid table name');
        }

        $userId = $input->getOption('start-user-id');
        $maxUsers = $input->getOption('max-user');

        $this->dryRun = false;
        $this->dryRun = $input->getOption('dry-run');

        $time = new \DateTime;
        $msgNum = $this->process($userId, $maxUsers, $table);
        $this->end($time);
    }

    /**
     * 進行刪資料
     *
     * @param integer $startUserId 起始使用者編號
     * @param integer $maxUsers 每次處理的最大筆數
     * @param string $table 資料表名
     */
    private function process($startUserId, $maxUsers, $table)
    {
        $conn = $this->getEntityManager()->getConnection();

        $this->output->writeln((new \DateTime)->format(\DateTime::ISO8601));

        $stmt = $conn->prepare(
            "SELECT cn.user_id FROM {$table}_negative as cn " .
            "INNER JOIN $table as c ON cn.user_id = c.user_id " .
            'WHERE cn.user_id > :uid AND c.negative = 0 ' .
            "ORDER BY cn.user_id ASC LIMIT $maxUsers"
        );

        $prevUserId = -1;
        $currUserId = $startUserId;

        while ($prevUserId != $currUserId) {
            $stmt->bindValue('uid', $currUserId);
            $stmt->execute();

            $count = 0;
            $sql = '';
            $prevUserId = $currUserId;

            while ($cash = $stmt->fetch()) {
                $userId = $cash['user_id'];
                $currUserId = $userId;
                $delSql = "DELETE FROM {$table}_negative WHERE user_id = $userId;";
                $sql .= $delSql;
                $this->output->writeln($delSql);
                $count ++;
            }

            if (!$this->dryRun && $count) {
                $conn->executeUpdate($sql);
                $this->output->writeln('Flush');

                usleep(500000);
            }
        }

        $this->output->writeln('Max user id = ' . $currUserId);
    }

    /**
     * 程式結束顯示處理時間、記憶體
     *
     * @param \DateTime $startTime 開始時間
     */
    private function end(\DateTime $startTime)
    {
        $endTime = new \DateTime;
        $costTime = $endTime->diff($startTime, true);
        $this->output->writeln('Execute time: ' . $costTime->format('%H:%I:%S'));

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);
        $this->output->writeln("Memory MAX use: $usage M");
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
