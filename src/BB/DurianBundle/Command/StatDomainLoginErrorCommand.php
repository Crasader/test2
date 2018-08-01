<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * 統計所有廳異常登入IP
 */
class StatDomainLoginErrorCommand extends ContainerAwareCommand
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
     * 搜尋起始日期
     *
     * @var \DateTime
     */
    private $startDate;

    /**
     * 搜尋結束日期
     *
     * @var \DateTime
     */
    private $endDate;

    /**
     * 執行起始時間
     *
     * @var \DateTime
     */
    private $startTime;

    /**
     * 執行結束時間
     *
     * @var \DateTime
     */
    private $endTime;

    /**
     * 查詢結果資料
     *
     * @var array
     */
    private $results;

    /**
     * 準備寫入csv資料
     *
     * @var array
     */
    private $outputContent;

    /**
     * output 路徑
     *
     * @var string
     */
    private $outputPath;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('durian:stat-domain-login-error')
            ->setDescription('統計所有廳區間內符合條件之異常登入IP')
            ->addArgument('start-date', InputArgument::REQUIRED, '查詢起始日期')
            ->addArgument('end-date', InputArgument::REQUIRED, '查詢結束日期')
            ->addArgument('output-path', InputArgument::REQUIRED, 'CSV輸出路徑')
            ->setHelp(<<<EOT
統計 2018-02-08 00:00:00 ~ 2018-02-08 23:59:59 區間，並輸出CSV至當前目錄
$ ./console durian:stat-domain-login-error '2018-02-08 00:00:00' '2018-02-08 23:59:59' ./stat-domain-login-error.csv
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
        $this->start();
        $this->runStat();
        $this->end();
    }

    /**
     * 取得命令列參數
     */
    private function getOpt()
    {
        $this->startDate = new \DateTime($this->input->getArgument('start-date'));
        $this->endDate = new \DateTime($this->input->getArgument('end-date'));
        $this->outputPath = $this->input->getArgument('output-path');
    }

    /**
     * 輸出 csv 格式檔案
     */
    private function writeOutputFile()
    {
        // 取得輸出內容及路徑
        $outputContent = $this->outputContent;
        $outputPath = $this->outputPath;

        // 清空檔案
        $file = fopen($outputPath, 'w+');
        fclose($file);

        if (is_array($outputContent)) {
            foreach ($outputContent as $data) {
                $line = implode(',', $data);
                file_put_contents($this->outputPath, "$line\n", FILE_APPEND);
            }
        }
    }

    /**
     * 統計所有廳區間內登入回傳為以下結果且單一IP累計共超過30次的名單 :
     * 1.密碼錯誤
     * 2.已凍結
     * 3.密碼錯誤並凍結使用者
     */
    private function getLoginErrPerIpOfDomain()
    {
        $conn = $this->getContainer()->get('doctrine.dbal.default_connection');
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $domainRepo = $em->getRepository('BBDurianBundle:DomainConfig');

        $domains = $domainRepo->getEnableDomain();
        $results = [];

        foreach ($domains as $domain) {
            $sql = 'SELECT domain, ip, client_os, count(1) AS count FROM `login_log` ' .
                'WHERE result in (3, 5, 9) AND at >= ? AND at <= ? ' .
                'AND domain = ? ' .
                'AND role = 1 GROUP BY ip HAVING count > 30';

            $params = [
                $this->startDate->format('Y-m-d H:i:s'),
                $this->endDate->format('Y-m-d H:i:s'),
                $domain
            ];

            $query = $conn->fetchAll($sql, $params);

            $results = array_merge($results, $query);
        }

        $this->results = $results;
    }

    /**
     * Run Stat
     */
    private function runStat()
    {
        $this->getLoginErrPerIpOfDomain();

        $outputContent = [];

        foreach ($this->results as $result) {
            $content = [
                $result['domain'],
                long2ip($result['ip']),
                $result['client_os'],
                $result['count']
            ];

            array_push($outputContent, $content);
        }

        $this->outputContent = $outputContent;
        $this->writeOutputFile();
    }

    /**
     * 開始執行、紀錄開始時間
     */
    private function start()
    {
        $this->startTime = new \DateTime;
        $this->output->writeln('Start Processing');
    }

    /**
     * 程式結束顯示處理時間、記憶體
     */
    private function end()
    {
        $endTime = new \DateTime;
        $costTime = $endTime->diff($this->startTime, true);

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);

        $this->output->writeln('Execute time: ' . $costTime->format('%H:%I:%S'));
        $this->output->writeln("Memory MAX use: $usage M");
        $this->output->writeln('Finish');
    }
}
