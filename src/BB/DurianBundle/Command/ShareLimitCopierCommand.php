<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\ShareUpdateCron;

/**
 * 佔成複製器
 * app/console durian:share:copy c 2 12 1
 */
class ShareLimitCopierCommand extends ContainerAwareCommand
{
    /**
     * ShareUpdateCron::EVERY_MON_12PM
     */
    const EVERY_MON_PERIOD = '0 12 * * 1';

    /**
     * ShareUpdateCron::EVERY_DAY_12AM
     */
    const EVERY_DAY_PERIOD = '0 0 * * *';

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * 佔成更新週期
     *
     * @var string
     */
    private $period;

    /**
     * 轉移資料總筆數
     */
    private $records;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:share:copy')
            ->setDescription('複製佔成資料')
            ->setDefinition(array())
            ->addArgument('mode', InputArgument::REQUIRED, '執行模式')
            ->addArgument('from', InputArgument::REQUIRED, '複製來源的Group')
            ->addArgument('to', InputArgument::REQUIRED, '新增佔成的Group')
            ->addArgument('update-cron', InputArgument::REQUIRED, '新增佔成的share update cron')
            ->addOption('domain', null, InputOption::VALUE_OPTIONAL, '指定domain', null)
            ->setHelp(<<<EOT
複製佔成資料
mode可輸入:
    'c':copy 直接複製一份指定的來源佔成到新的Group(不支援'domain'參數)
    's':scan 逐筆檢查並更新來源佔成資料到新的Group(支援'domain'參數)
update-cron可輸入:
    '1':ShareUpdateCron::EVERY_MON_12PM
    '2':ShareUpdateCron::EVERY_DAY_12AM
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

        $this->checkArgument();
        $mode = $input->getArgument('mode');

        $startTime = microtime(true);

        if ($mode == 'c') {
            $this->copy();
        } else {
            $this->scan();
        }

        $endTime = microtime(true);

        $excutionTime = round($endTime - $startTime, 1);
        $timeString = $excutionTime . ' sec.';

        if ($excutionTime > 60) {
            $timeString = round($excutionTime / 60, 0) . ' mins.';
        }

        $output->write("\nExecute time: $timeString", true);
        $output->write("Total: ".$this->records." records.", true);

        $performance = round($this->records / $excutionTime, 0);
        $output->write("Speed: $performance records/s", true);

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage  = number_format($memory, 2);
        $output->write("Memory MAX use: $usage M", true);
    }

    /**
     * 回傳DB連線
     *
     * @return \Doctrine\DBAL\Connection
     */
    protected function getConn()
    {
        if (!empty($this->conn)) {
            return $this->conn;
        }

        $this->conn = $this->getContainer()->get('doctrine.dbal.default_connection');
        return $this->conn;
    }

    /**
     * 檢查輸入參數
     */
    private function checkArgument()
    {
        $conn = $this->getConn();
        $mode = $this->input->getArgument('mode');
        $from = $this->input->getArgument('from');
        $updateCron = $this->input->getArgument('update-cron');
        $errs = array();

        // check 'from'
        $query = "SELECT id FROM share_limit WHERE group_num = ? LIMIT 1";
        $shareId = $conn->fetchColumn($query, array($from));

        if (!$shareId) {
            $errs[] = "[ERROR]Source ShareLimit group: $from has no data.";
        }

        // check 'mode'
        if ($mode == 'c') {
            $to = $this->input->getArgument('to');

            $query = "SELECT id FROM share_limit WHERE group_num = ? LIMIT 1";
            $shareId = $conn->fetchColumn($query, array($to));

            if ($shareId) {
                $errs[] = "[ERROR]Already has ShareLimit Group: $to, please use mode 's' to update data.";
            }
        } elseif ($mode != 's') {
            $errs[] = "[ERROR]Sysrem not support this mode:'$mode'";
        }

        // check 'updateCron'
        if ($updateCron == ShareUpdateCron::EVERY_MON_12PM) {
            $this->period = self::EVERY_MON_PERIOD;
        } elseif ($updateCron == ShareUpdateCron::EVERY_DAY_12AM) {
            $this->period = self::EVERY_DAY_PERIOD;
        } else {
            $errs[] = "[ERROR]System not support this updateCron:'$updateCron'";
        }

        if ($errs) {
            foreach ($errs as $errMsg) {
                $this->output->write($errMsg, true);
            }

            throw new \InvalidArgumentException('Argument is not valid.');
        }
    }

    /**
     * 直接複製指定的佔成
     */
    private function copy()
    {
        $conn = $this->getConn();
        $from = $this->input->getArgument('from');
        $to   = $this->input->getArgument('to');

        $this->output->write("Insert ShareLimit Group: $to...", true);

        // ShareLimit
        $query = "INSERT INTO `share_limit`(`user_id`, `group_num`, `upper`, `lower`, `parent_upper`, `parent_lower`, `min1`, `max1`, `max2`) ".
                 "SELECT `user_id`, ? AS `group_num`, `upper`, `lower`, `parent_upper`, `parent_lower`, `min1`, `max1`, `max2` ".
                 "FROM share_limit ".
                 "WHERE group_num = ?";
        $this->records = $conn->executeUpdate($query, array($to, $from));

        $this->output->write("$this->records records inserted.", true);

        // ShareLimitNext
        $this->output->write("Insert ShareLimitNext Group: $to...", true);

        $query = "INSERT share_limit_next ".
                 "SELECT * ".
                 "FROM share_limit ".
                 "WHERE id > (SELECT MAX(id) FROM `share_limit_next`)";
        $slAffect = $conn->executeUpdate($query);

        $query = "UPDATE ".
                    "`share_limit_next` AS a, ".
                    "(SELECT `user_id`, `group_num`, `upper`, `lower`, `parent_upper`, `parent_lower`, `min1`, `max1`, `max2` ".
                     "FROM share_limit_next WHERE group_num = ?) AS b ".
                 "SET a.`upper` = b.`upper`, ".
                     "a.`lower` = b.`lower`, ".
                     "a.`parent_upper` = b.`parent_upper`, ".
                     "a.`parent_lower` = b.`parent_lower`, ".
                     "a.`min1` = b.`min1`, ".
                     "a.`max1` = b.`max1`, ".
                     "a.`max2` = b.`max2` ".
                 "WHERE a.user_id = b.user_id AND a.group_num = ?";
        $conn->executeUpdate($query, array($from, $to));

        $this->records += $slAffect;
        $this->output->write("$slAffect records inserted.", true);

        $this->checkShareUpdateCron();
    }

    /**
     * 逐筆檢查並複製佔成
     */
    private function scan()
    {
        $conn = $this->getConn();
        $domain = $this->input->getOption('domain');
        $domains = array();

        if ($domain) {
            $domains[] = $domain;
        } else {
            $domains = $this->getAllDomainId();
        }

        foreach ($domains as $domain) {
            $this->output->write("Update domain: $domain...", true);

            $this->scanShareLimit($domain);
            $this->scanShareLimitNext($domain);
            $this->records++;

            $query = "SELECT u.id FROM user u, user_ancestor ua WHERE u.id = ua.user_id AND ua.ancestor_id = ?";
            $statement = $conn->executeQuery($query, array($domain));

            while ($data = $statement->fetch(\PDO::FETCH_ASSOC)) {

                $this->scanShareLimit($data['id']);
                $this->scanShareLimitNext($data['id']);

                $this->records++;
            }
        }

        $this->output->write("$this->records users updated.", true);

        $this->checkShareUpdateCron();
    }

    /**
     * 檢查佔成
     */
    private function scanShareLimit($uid)
    {
        $conn = $this->getConn();
        $from = $this->input->getArgument('from');
        $to   = $this->input->getArgument('to');

        // ShareLimit
        $query = "SELECT id FROM share_limit WHERE user_id = ? AND group_num = ?";
        $shareId = $conn->fetchColumn($query, array($uid, $to));

        if ($shareId) {
            // exist => update
            $query = "UPDATE ".
                         "`share_limit` AS a, ".
                         "(SELECT `upper`, `lower`, `parent_upper`, `parent_lower`, `min1`, `max1`, `max2` ".
                          "FROM share_limit ".
                          "WHERE user_id = ? ".
                          "AND group_num = ?) AS b ".
                     "SET a.`upper` = b.`upper`, ".
                         "a.`lower` = b.`lower`, ".
                         "a.`parent_upper` = b.`parent_upper`, ".
                         "a.`parent_lower` = b.`parent_lower`, ".
                         "a.`min1` = b.`min1`, ".
                         "a.`max1` = b.`max1`, ".
                         "a.`max2` = b.`max2` ".
                     "WHERE a.id = ?";
            $conn->executeUpdate($query, array($uid, $from, $shareId));

        } else {
            // not exist => insert
            $query = "INSERT INTO `share_limit`(`user_id`, `group_num`, `upper`, `lower`, `parent_upper`, `parent_lower`, `min1`, `max1`, `max2`) ".
                     "SELECT `user_id`, '$to' AS `group_num`, `upper`, `lower`, `parent_upper`, `parent_lower`, `min1`, `max1`, `max2` ".
                     "FROM share_limit ".
                     "WHERE user_id = ? ".
                     "AND group_num = ?";
            $conn->executeUpdate($query, array($uid, $from));
        }
    }

    /**
     * 檢查預改佔成
     */
    private function scanShareLimitNext($uid)
    {
        $conn = $this->getConn();
        $from = $this->input->getArgument('from');
        $to   = $this->input->getArgument('to');

        // ShareLimitNext
        $query = "SELECT id FROM share_limit_next WHERE user_id = ? AND group_num = ?";
        $shareId = $conn->fetchColumn($query, array($uid, $to));

        if ($shareId) {
            // exist => update
            $query = "UPDATE ".
                         "`share_limit_next` AS a, ".
                         "(SELECT `upper`, `lower`, `parent_upper`, `parent_lower`, `min1`, `max1`, `max2` ".
                          "FROM share_limit_next ".
                          "WHERE user_id = ? ".
                          "AND group_num = ?) AS b ".
                     "SET a.`upper` = b.`upper`, ".
                         "a.`lower` = b.`lower`, ".
                         "a.`parent_upper` = b.`parent_upper`, ".
                         "a.`parent_lower` = b.`parent_lower`, ".
                         "a.`min1` = b.`min1`, ".
                         "a.`max1` = b.`max1`, ".
                         "a.`max2` = b.`max2` ".
                     "WHERE a.id = ?";
            $conn->executeUpdate($query, array($uid, $from, $shareId));

        } else {
            // not exist => insert
            $query = "SELECT id FROM share_limit WHERE user_id = ? AND group_num = ?";
            $shareId = $conn->fetchColumn($query, array($uid, $to));

            $query = "INSERT INTO `share_limit_next`(`id`, `user_id`, `group_num`, `upper`, `lower`, `parent_upper`, `parent_lower`, `min1`, `max1`, `max2`) ".
                     "SELECT ? AS `id`, `user_id`, ? AS `group_num`, `upper`, `lower`, `parent_upper`, `parent_lower`, `min1`, `max1`, `max2` ".
                     "FROM share_limit_next ".
                     "WHERE user_id = ? ".
                     "AND group_num = ?";
            $params = array(
                $shareId,
                $to,
                $uid,
                $from
            );
            $conn->executeUpdate($query, $params);
        }
    }

    /**
     * 檢查ShareUpdateCron是否存在並與輸入的設定相符
     */
    private function checkShareUpdateCron()
    {
        $conn = $this->getConn();
        $from = $this->input->getArgument('from');
        $to   = $this->input->getArgument('to');

        $query = "SELECT period FROM share_update_cron WHERE group_num = ? LIMIT 1";
        $updateCron = $conn->fetchAll($query, array($to));

        if ($updateCron) {
            if ($updateCron[0]['period'] != $this->period) {
                $params = array('period' => $this->period);
                $identifier = array('group_num' => $to);
                $conn->update('share_update_cron', $params, $identifier);
            }
        } else {
            $query = "SELECT update_at, state FROM share_update_cron WHERE group_num = ? LIMIT 1";
            $fromUpdateCron = $conn->fetchAll($query, array($from));

            $params = array(
                'group_num'   => $to,
                'period'      => $this->period,
                'update_at'   => $fromUpdateCron[0]['update_at'],
                'state'       => $fromUpdateCron[0]['state'],
            );

            $conn->insert('share_update_cron', $params);
        }

        $this->output->write("ShareUpdateCron Group: $to check OK.", true);
    }

    /**
     * 取得所有domain的id
     *
     * @return array
     */
    public function getAllDomainId()
    {
        $conn = $this->getConn();
        $domains = array();

        $query = "SELECT id FROM user WHERE parent_id IS NULL";
        $users = $conn->fetchAll($query);

        foreach ($users as $user) {
            $domains[] = (int) $user['id'];
        }

        return $domains;
    }
}
