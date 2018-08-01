<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 產生jenkins上排程背景的執行時間
 *
 * @author sin-hao 2015.04.10
 */
class TimeGeneratorCommand extends ContainerAwareCommand
{
    /**
     * 目前的DB連線設定
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var string
     */
    private $commandName;

    /**
     * jenkins上排程的觸發週期為一天
     *
     * @var boolean
     */
    private $day = false;

    /**
     * jenkins上排程的觸發週期為每小時
     *
     * @var boolean
     */
    private $hour = false;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:time-generator')
            ->setDescription('產生jenkins上排程背景的執行時間')
            ->addOption('commandName', null, InputOption::VALUE_OPTIONAL, '背景名稱')
            ->addOption('day', null, InputOption::VALUE_NONE, '產生jenkins觸發週期為一天的時間參數')
            ->addOption('hour', null, InputOption::VALUE_NONE, '產生jenkins觸發週期為每小時的時間參數')
            ->setHelp(<<<EOT
產生jenkins上排程背景的執行時間-產生jenkins觸發週期為一天的時間參數
$ ./console durian:time-generator --day --commandName=abc

產生jenkins上排程背景的執行時間-產生jenkins觸發週期為每小時的時間參數
$ ./console durian:time-generator --hour --commandName=abc

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

        $this->getOpt();

        if ($this->day) {
            $this->timeForDay();

            return;
        }

        if ($this->hour) {
            $this->timeForHour();
        }
    }

    /**
     * 產生jenkins觸發週期為一天的時間參數
     */
    private function timeForDay()
    {
        $conn = $this->getConnection();

        $sql = 'SELECT end_at, last_end_time FROM `background_process` WHERE name = ?';
        $param = [$this->commandName];
        $result = $conn->fetchAll($sql, $param);

        $now = new \DateTime('now');
        //預設最後背景執行時間為前一天
        $lastExecute = $now->sub(new \DateInterval('P1D'));

        $now = new \DateTime('now');
        if ($result[0]['end_at'] != null) {
            $endAt = new \DateTime("{$result[0]['end_at']}");
            //第一次執行的背景，background_process初始化的end_at可能是非常久之前的時間，
            //超過30天為標準，end_at不可以拿來當使用
            $Standard = (strtotime($now->format('Y-m-d')) - strtotime($endAt->format('Y-m-d')))/60/60/24;
            if ($Standard <= 30) {
                $lastExecute = $endAt;
            }
        }

        //如果有紀錄最後一次背景成功執行所帶入的結束時間參數則優先取用
        if ($result[0]['last_end_time'] != null) {
            $lastExecute = new \DateTime("{$result[0]['last_end_time']}");
        }

        $startDay = $lastExecute->format('Y-m-d');
        $endDay = $now->format('Y-m-d');

        $this->output->write("$startDay,");
        $this->output->write($endDay);
    }

    /**
     * 產生jenkins觸發週期為每小時的時間參數
     */
    private function timeForHour()
    {
        $conn = $this->getConnection();

        $sql = 'SELECT end_at, last_end_time FROM `background_process` WHERE name = ?';
        $param = [$this->commandName];
        $result = $conn->fetchAll($sql, $param);

        $now = new \DateTime('now');
        $lastExecute = clone $now;

        if ($result[0]['end_at'] != null) {
            $endAt = new \DateTime("{$result[0]['end_at']}");
            //第一次執行的背景，background_process初始化的end_at可能是非常久之前的時間，
            //超過48小時為標準，end_at不可以拿來當使用
            $standard = (strtotime($now->format('Y-m-d H:00:00')) - strtotime($endAt->format('Y-m-d H:00:00')))/60/60;

            if ($standard <= 48) {
                $lastExecute = $endAt->add(new \DateInterval('PT1H'));
            }
        }

        //如果有紀錄最後一次背景成功執行所帶入的結束時間參數則優先取用
        if ($result[0]['last_end_time'] != null) {
            $lastExecute = new \DateTime("{$result[0]['last_end_time']}");
        }

        $now->add(new \DateInterval('PT1H'));
        $endHour = $now->format('Y-m-d H:00:00');
        $startHour = $lastExecute->format('Y-m-d H:00:00');

        $this->output->write("$startHour,");
        $this->output->write($endHour);
    }

    /**
     * 取得區間參數
     *
     * @throws \Exception
     */
    private function getOpt()
    {
        $this->day = $this->input->getOption('day');
        $this->hour = $this->input->getOption('hour');
        $this->commandName = $this->input->getOption('commandName');
    }

    /**
     * 回傳Default DB連線
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
}
