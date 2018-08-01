<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckShareLimitMinMaxCommand extends ContainerAwareCommand
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
     * 一次抓幾筆佔成
     *
     * @var int
     */
    private $limit = 1000;

    /**
     * 資料筆數
     *
     * @var int
     */
    private $total = 0;

    /**
     * 錯誤筆數
     *
     * @var int
     */
    private $errCount = 0;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:check-sharelimit-min-max')
            ->setDescription('檢查佔成MinMax欄位')
            ->addOption('now', null, InputOption::VALUE_NONE, '檢查現行佔成')
            ->addOption('next', null, InputOption::VALUE_NONE, '檢查預改佔成')
            ->setHelp(<<<EOT
檢查佔成MinMax欄位，預設檢查現行與預改佔成
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

        $curDate = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $dateStr = $curDate->format('Y-m-d H:i:s');
        $output->write("{$dateStr} : Begin checking sharelimit min max...", true);

        $sql = 'SELECT domain as id, name FROM domain_config';
        $domains = $this->getConnection('share')->fetchAll($sql);

        foreach ($domains as $domain) {
            $domainId    = $domain['id'];
            $domainName  = $domain['name'];
            $this->output->write("Start checking 廳名: $domainName ");

            if ($this->input->getOption('now')) {
                $this->handle($domainId, 'share_limit');
            } elseif ($this->input->getOption('next')) {
                $this->handle($domainId, 'share_limit_next');
            } else {
                $this->handle($domainId, 'share_limit');
                $this->handle($domainId, 'share_limit_next');
            }

            $this->output->write("Finish checking 廳名: $domainName.", true);
        }

        $this->output->write("Total checked: $this->total; Error: $this->errCount", true);

        $endDate = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $dateStr = $endDate->format('Y-m-d H:i:s');
        $output->write("{$dateStr} : Finish checking sharelimit min max.", true);
    }

    /**
     * 檢查佔成或預改佔成的MinMax欄位
     *
     * @param int $domain
     * @param string $table
     */
    private function handle($domain, $table)
    {
        // 明確指定這裡會用到的資料表，以避免語法錯誤
        $this->isAllowTable($table);

        $total = $error = 0;
        $this->output->write("$table...", true);

        // 從廳主開始，只檢查大股東~總代，代理跟會員的設定是用特殊設定的不需檢查
        for ($depth = 1; $depth <= 3; $depth++) {

            $offset = 0;
            $this->output->write("Depth : $depth...", true);

            while (1) {

                $sql = "SELECT s.id, s.user_id, s.group_num, s.min1, s.max1, s.max2 ".
                       "FROM `$table` s, user_ancestor ua ".
                       "WHERE s.user_id = ua.user_id AND ua.depth = ? AND ua.ancestor_id = ?".
                       "LIMIT $offset, $this->limit";
                $shares = $this->getConnection()->fetchAll($sql, array($depth, $domain));

                if (empty($shares)) {
                    break;
                }

                foreach ($shares as $share) {
                    $total++;
                    $this->total++;

                    $min1Result = $this->checkMin1($table, $share);
                    $max1Result = $this->checkMax1($table, $share);
                    $max2Result = $this->checkMax2($table, $share);

                    if (!$min1Result || !$max1Result || !$max2Result) {
                        $error++;
                        $this->errCount++;
                    }
                }

                $offset += $this->limit;
            }
        }

        $this->output->write("Domain checked: $total; Error: $error", true);
    }

    /**
     * 回傳 Connection 物件
     *
     * @param string $name Connection 名稱
     * @return \Doctrine\DBAL\Connection
     */
    protected function getConnection($name = 'default')
    {
        return $this->getContainer()->get("doctrine.dbal.{$name}_connection");
    }

    /**
     * 檢查min1是否正確
     *
     * @param string $table
     * @param array  $share
     * @return boolean
     */
    private function checkMin1($table, $share)
    {
        // 明確指定這裡會用到的資料表，以避免語法錯誤
        $this->isAllowTable($table);

        $shareId    = $share['id'];
        $uid        = $share['user_id'];
        $group      = $share['group_num'];
        $originMin1 = $share['min1'];

        $sql = "SELECT min(s.parent_upper + s.lower) ".
               "FROM `$table` s, user u ".
               "WHERE s.user_id = u.id ".
               "AND u.parent_id = ? ".
               "AND s.group_num = ?";
        $min1 = $this->getConnection()->fetchColumn($sql, array($uid, $group));

        if (null === $min1) {
            $min1 = 200;
        }

        if ($min1 != $originMin1) {
            $this->output->write(
                "[ERROR]Min1 ShareId: $shareId, UserId: $uid, Group: $group; origin: [$originMin1] != [$min1]",
                true
            );
            return false;
        }

        return true;
    }

    /**
     * 檢查max1是否正確
     *
     * @param string $table
     * @param array  $share
     * @return boolean
     */
    private function checkMax1($table, $share)
    {
        // 明確指定這裡會用到的資料表，以避免語法錯誤
        $this->isAllowTable($table);

        $shareId    = $share['id'];
        $uid        = $share['user_id'];
        $group      = $share['group_num'];
        $originMax1 = $share['max1'];

        $sql = "SELECT max(s.parent_upper) ".
               "FROM `$table` s, user u ".
               "WHERE s.user_id = u.id ".
               "AND u.parent_id = ? ".
               "AND s.group_num = ?";
        $max1 = $this->getConnection()->fetchColumn($sql, array($uid, $group));

        if (null === $max1) {
            $max1 = 0;
        }

        if ($max1 != $originMax1) {
            $this->output->write(
                "[ERROR]Max1 ShareId: $shareId, UserId: $uid, Group: $group; origin: [$originMax1] != [$max1]",
                true
            );
            return false;
        }

        return true;
    }

    /**
     * 檢查max2是否正確
     *
     * @param string $table
     * @param array  $share
     * @return boolean
     */
    private function checkMax2($table, $share)
    {
        // 明確指定這裡會用到的資料表，以避免語法錯誤
        $this->isAllowTable($table);

        $shareId    = $share['id'];
        $uid        = $share['user_id'];
        $group      = $share['group_num'];
        $originMax2 = $share['max2'];

        $sql = "SELECT max(s.parent_lower + s.upper) ".
               "FROM `$table` s, user u ".
               "WHERE s.user_id = u.id ".
               "AND u.parent_id = ? ".
               "AND s.group_num = ?";
        $max2 = $this->getConnection()->fetchColumn($sql, array($uid, $group));

        if (null === $max2) {
            $max2 = 0;
        }

        if ($max2 != $originMax2) {
            $this->output->write(
                "[ERROR]Max2 ShareId: $shareId, UserId: $uid, Group: $group; origin: [$originMax2] != [$max2]",
                true
            );
            return false;
        }

        return true;
    }

    /**
     * 檢查是否為允許使用的資料表
     *
     * @param string $tableName 資料表名稱
     * @return boolean
     */
    private function isAllowTable($tableName)
    {
        $allowTables = array(
            'share_limit',
            'share_limit_next'
        );

        if (in_array($tableName, $allowTables)) {
            return true;
        }

        throw new \Exception('Not allowed table');
    }
}
