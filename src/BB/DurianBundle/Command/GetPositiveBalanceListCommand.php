<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 取得某個廳bb額度加沙巴額度大於0的會員名單
 */
class GetPositiveBalanceListCommand extends ContainerAwareCommand
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
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * 是否只計算BB額度
     *
     * @var Boolean
     */
    private $onlyBB;

    /**
     * 廳主id
     *
     * @var Integer
     */
    private $domain;

    /**
     * 存沙巴額度的檔案路徑
     *
     * @var String
     */
    private $sabaFile;

    /**
     * 存所有沙巴額度的資料
     *
     * @var Array
     */
    private $sabaBalance = array();

    /**
     * 準備輸出到檔案的內容
     *
     * @var Array
     */
    private $outputContent = array();

    /**
     * 撈出會員額度大於此值的名單
     *
     * @var integer
     */
    private $balanceBound = 0;

    /**
     * 目前的DB連線設定
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * 輸出檔的路徑
     *
     * @var String
     */
    private $outputPath;

    /**
     *  csv檔的分隔符號
     */
    const SEPARATOR = ',';

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:get-positive-balance-list')
            ->setDescription('取得某個廳bb額度+沙巴額度大於0的會員名單')
            ->addOption('only-bb', null, InputOption::VALUE_NONE, 'Only count bb balance')
            ->addOption('domain', null, InputOption::VALUE_REQUIRED, 'Domain')
            ->addOption('saba', null, InputOption::VALUE_REQUIRED, 'The file contains saba balance')
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Output file')
            ->setHelp(<<<EOT
取得某個廳bb額度大於0的會員名單
app/console durian:get-positive-balance-list --only-bb --domain=3(預設輸出檔為專案根目錄的output.csv)

取得某個廳bb額度+沙巴額度大於0的會員名單
app/console durian:get-positive-balance-list --domain=3 --saba=saba.csv (預設輸出檔為專案根目錄的output.csv)
app/console durian:get-positive-balance-list --domain=3 --saba=saba.csv --output=output.csv

沙巴檔案的格式為:
使用者id, 沙巴額度

輸出的會員名單的格式為:
使用者名稱, bb額度, 沙巴額度
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

        $this->getArguments();

        $domainUser = $this->getEntityManager()
                           ->find('BB\DurianBundle\Entity\User', $this->domain);
        if (!$domainUser) {
            throw new \Exception('Domain not exist');
        }

        $domainName = $domainUser->getUsername();

        $str = "Start processing domain {$domainName} (domain id {$this->domain}).";
        $this->output->write($str, true);

        $this->process();
        $this->output->write('Finish.', true);
    }

    /**
     * 取得命令列參數
     *
     * @throws \Exception
     */
    private function getArguments()
    {
        $this->onlyBB = $this->input->getOption('only-bb');
        $this->sabaFile = $this->input->getOption('saba');
        $this->domain = $this->input->getOption('domain');
        $this->outputPath = $this->input->getOption('output');

        if (empty($this->outputPath)) {
            $this->outputPath = $this->getContainer()
                                     ->get('kernel')
                                     ->getRootDir()."/../output.csv";
        }

        if (empty($this->sabaFile) && !$this->onlyBB) {
            throw new \InvalidArgumentException('No saba file specified.');
        }

        if (empty($this->domain)) {
            throw new \InvalidArgumentException('No domain specified.');
        }
    }

    /**
     * 開始執行取得會員額度大於某個值的名單
     */
    private function process()
    {
        if (!$this->onlyBB) {
            $this->getSabaData();
        }
        $this->getMemberData();
        $this->getResultList();
        $this->writeOutputFile();
    }

    /**
     * 每個會員的bb額度與沙巴額度的總和若大於0, 就存到$this->outputContent
     */
    private function getResultList()
    {
        foreach ($this->allMembers as $member) {
            $userId      = $member['id'];
            $bbBalance   = $member['balance'];
            $sabaBalance = 0;
            $username    = $member['username'];

            if (array_key_exists($userId, $this->sabaBalance)) {
                $sabaBalance = $this->sabaBalance[$userId];
            }

            if (($bbBalance + $sabaBalance) > $this->balanceBound) {
                /*
                 * 寫到csv檔的資料前面都加單引號, 以便讓open office將數字判斷成字串
                 */
                $outputContent = array(
                    "'$username",
                    "'$bbBalance"
                );
                if (!$this->onlyBB) {
                    $outputContent[] = "'$sabaBalance";
                }
                $this->outputContent[] = $outputContent;
            }
        }
    }

    /**
     * 撈出某個廳的所有會員, 把userid, username, balance, 存到$this->allMembers
     */
    private function getMemberData()
    {
        $sql = "SELECT u.id, u.username, c.balance
                FROM user as u, cash as c
                WHERE u.id = c.user_id AND u.domain = ?
                AND u.test = 0 AND u.role = 1 AND sub = 0";

        $this->allMembers = $this->getConnection()->fetchAll($sql, array($this->domain));
    }

    /**
     * 輸出會員額度大於0名單
     */
    private function writeOutputFile()
    {
        // 清空檔案內容
        $file = fopen($this->outputPath, 'w+');
        fclose($file);

        foreach ($this->outputContent as $data) {
            $line = implode(',', $data);
            file_put_contents($this->outputPath, "$line\n", FILE_APPEND);
        }
    }

    /**
     * 讀檔把沙巴額度的資料存到$this->sabaAmount
     *
     * @throws \Exception
     */
    private function getSabaData()
    {
        if (($sabaFile = fopen($this->sabaFile, 'r')) == false) {
            throw new \Exception('Fail to open saba file');
        }

        while (($data = fgetcsv($sabaFile, 1000, self::SEPARATOR)) !== false) {
            $userId = $data[0];
            $amount = $data[1];
            $this->sabaBalance[$userId] = $amount;
        }

        fclose($sabaFile);
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
}
