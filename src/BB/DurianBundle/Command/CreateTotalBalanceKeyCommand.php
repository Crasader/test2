<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\CashTotalBalance;
use BB\DurianBundle\Entity\CashFakeTotalBalance;

class CreateTotalBalanceKeyCommand extends ContainerAwareCommand
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
     * 資料表名稱
     *
     * @var string
     */
    private $table;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:create-total-balance-key')
            ->setDescription('建立會員總餘額記錄redis key')
            ->addOption('table', null, InputOption::VALUE_REQUIRED, '資料表名稱')
            ->addOption('domain', null, InputOption::VALUE_OPTIONAL, '指定廳')
            ->addOption('start', null, InputOption::VALUE_OPTIONAL, '指定起始廳')
            ->addOption('end', null, InputOption::VALUE_OPTIONAL, '指定結束廳')
            ->setHelp(<<<EOT
建立全部廳會員現金總餘額redis key
app/console durian:create-total-balance-key --table=cash

建立指定廳會員現金總餘額redis key
app/console durian:create-total-balance-key --table=cash --domain=6

建立指定範圍廳會員現金總餘額redis key
app/console durian:create-total-balance-key --table=cash --start=6 --end=163

建立全部廳會員假現金總餘額redis key
app/console durian:create-total-balance-key --table=cash_fake

建立指定廳會員假現金總餘額redis key
app/console durian:create-total-balance-key --table=cash_fake --domain=20
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->table = $this->input->getOption('table');
        $domain = $this->input->getOption('domain');
        $start = $this->input->getOption('start');
        $end = $this->input->getOption('end');

        $curDate = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $dateStr = $curDate->format('Y-m-d H:i:s');
        $output->write("{$dateStr} : 開始更新會員總餘額記錄", true);
        $startTime = microtime(true);

        $this->createKey($domain, $start, $end);
        $this->printPerformance($startTime);
    }

    /**
     * 建立會員總餘額redis key
     *
     * @param integer $domain 指定廳
     * @param integer $start 起始廳
     * @param integer $end 結束廳
     */
    private function createKey($domain = 0, $start, $end)
    {
        $em = $this->getEntityManager();
        $conn = $this->getContainer()->get('doctrine.dbal.default_connection');
        $connShare = $this->getContainer()->get('doctrine.dbal.share_connection');
        $redis = $this->getContainer()->get('snc_redis.total_balance');

        if ($this->table == 'cash') {
            $table = 'Cash';
        } else if ($this->table == 'cash_fake') {
            $table = 'CashFake';
        } else {
            return;
        }

        $domains = [
            ['domain' => $domain]
        ];

        if (!$domain) {
            $sql = 'SELECT domain FROM domain_config WHERE enable = 1 AND removed = 0 ';

            if (!is_null($start)) {
                $sql .= "AND domain >= $start ";
            }

            if (!is_null($end)) {
                $sql .= "AND domain <= $end ";
            }

            $sql .= 'ORDER BY domain';

            $domains = $connShare->fetchAll($sql);
        }

        foreach ($domains as $domain) {
            $domainId = $domain['domain'];
            $userPayway = $em->find('BBDurianBundle:UserPayway', $domainId);
            $payWayEnable = 'is' . $table . 'Enabled';

            if (!$userPayway->$payWayEnable()) {
                continue;
            }

            $userId = 0;
            $balances = [];
            $testBalances = [];

            $searchSql = 'SELECT ua.user_id AS id, c.balance AS balance, c.currency AS currency ' .
                'FROM `user_ancestor` AS ua ' .
                'INNER JOIN `user` AS u ON ua.user_id = u.id ' .
                "INNER JOIN $this->table AS c ON ua.user_id = c.user_id " .
                'WHERE ua.ancestor_id = ? ' .
                'AND ua.depth = ? AND ua.user_id > ? ' .
                'AND u.test = ? AND u.sub = ? ' .
                'ORDER BY ua.user_id ASC LIMIT 10000';

            // 搜尋並加總會員額度
            while (1) {
                $results = $conn->fetchAll($searchSql, [$domainId, 5, $userId, 0, 0]);

                if (empty($results)) {
                    break;
                }

                foreach ($results as $result) {
                    $currency = $result['currency'];

                    if (!array_key_exists($currency, $balances)) {
                        $balances[$currency] = 0;
                    }

                    $balances[$currency] += $result['balance'];
                    $userId = $result['id'];
                }
            }

            foreach ($balances as $currency => $balance) {
                $key = $this->table . '_total_balance_' . $domainId . '_' . $currency;
                $redis->hset($key, 'normal', (int) round($balance * 10000));
                $this->output->writeln("$key: $balance");
            }

            $userId = 0;

            // 只有測試體系
            while (1) {
                $results = $conn->fetchAll($searchSql, [$domainId, 5, $userId, 1, 0]);

                if (empty($results)) {
                    break;
                }

                foreach ($results as $result) {
                    $currency = $result['currency'];

                    if (!array_key_exists($currency, $testBalances)) {
                        $testBalances[$currency] = 0;
                    }

                    $testBalances[$currency] += $result['balance'];
                    $userId = $result['id'];
                }
            }

            foreach ($testBalances as $currency => $testBalance) {
                $key = $this->table . '_total_balance_' . $domainId . '_' . $currency;
                $redis->hset($key, 'test', (int) round($testBalance * 10000));
                $this->output->writeln("$key(test): $testBalance");
            }
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
            $timeString = round($excutionTime / 60, 1) . ' mins.';
        }
        $this->output->writeln("\nExecute time: $timeString");

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);
        $this->output->writeln("Memory MAX use: $usage M");
    }

    /**
     * 回傳 EntityManger 連線
     *
     * @param string $name EntityManager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }
}
