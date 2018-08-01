<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\User;

/**
 * 補單背景
 */
class ReOpCommand extends ContainerAwareCommand
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
     * @var Logger
     */
    private $logger;

    /**
     * 取消記錄sql log
     *
     * @var bool
     */
    private $disableLog;

    /**
     * 是否為試跑
     *
     * @var bool
     */
    private $dryRun = false;

    /**
     * OpService
     *
     * @var \BB\DurianBundle\Service\OpService
     */
    private $opService;

    /**
     * Validator
     *
     * @var \BB\DurianBundle\Service\Validator
     */
    private $validator;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:reop')
            ->setDescription('重新補單')
            ->addArgument('path', InputArgument::REQUIRED, 'CSV Path')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute the command as a dry run.')
            ->addOption('disable-log', null, InputOption::VALUE_NONE, 'Disable logging')
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'output file name')
            ->setHelp(<<<EOT
匯入csv來進行批次補單
輸入格式為：<info>userId,amount,opcode,refId,memo</info>
也可以自行在第一行定義欄位 ex: userId,amount,opcode,refId,memo

example: app/console durian:reop file.csv --dry-run --disable-log
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
        $this->disableLog = $this->input->getOption('disable-log');
        $this->dryRun = $this->input->getOption('dry-run');
        $this->opService = $this->getContainer()->get('durian.op');
        $this->validator = $this->getContainer()->get('durian.validator');

        $startTime = microtime(true);
        $this->log('ReOpCommand Start.');
        $output->write('ReOpCommand Start.', true);

        //把檔案讀出來
        $path = $this->input->getArgument('path');
        $csvArray = $this->getAllEntriesByCsv($path);

        //開輸出用的檔案
        $fileTitle = "帳號ID, 參考編號, 交易金額, 餘額, 使用者帳號, 廳名, 廳主代碼, memo, 交易類別, 幣別, 明細id\n";
        $fp = $this->readyOutputCsv($fileTitle);
        $output->write($fileTitle);
        $this->log($fileTitle);

        //開始op
        foreach ($csvArray as $key => $data) {
            $log = $this->operation($data, $this->dryRun);
            fwrite($fp, $log);
            $output->write($log);
            $this->log($log);

            if ($key % 10 == 0) {
                $this->getEntityManager()->clear();
            }

            unset($log);
            unset($key);
            unset($data);
        }

        unset($csvArray);
        fclose($fp);

        $endTime = microtime(true);

        $excutionTime = round($endTime - $startTime, 1);
        $timeString = $excutionTime . ' sec.';

        $output->write("\nExecute time: $timeString", true);
        $output->write("Memory MAX use: " . $this->getMemoryUseage() . " M", true);
        $output->write("ReOpCommand finish.", true);
        $this->log("ReOpCommand finish.");

        //關閉寫檔
        $this->logger->popHandler()->close();
    }

    /**
     * 準備輸出csv
     *
     * @param string $fileTitle
     * @return resource
     */
    private function readyOutputCsv($fileTitle)
    {
        $date = (new \DateTime('now'))->format("YmdHis");
        $filename = sprintf("output-%s.csv", $date);

        if ($this->input->getOption('output')) {
            $filename = $this->input->getOption('output');
        }

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $outputDir = "$logsDir/reop";

        if (!is_dir($outputDir)) {
            mkdir($outputDir);
        }

        $fp = fopen($outputDir."/".$filename, 'w');
        fwrite($fp, $fileTitle);

        return $fp;
    }

    /**
     * 回傳記憶體用量
     *
     * @return float
     */
    private function getMemoryUseage()
    {
        $memory = memory_get_peak_usage() / 1024 / 1024;

        return number_format($memory, 2);
    }

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name Entity Manager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 設定並記錄log
     *
     * @param String $message
     */
    private function log($msg)
    {
        if ($this->disableLog) {
            return;
        }

        if (null === $this->logger) {
            $this->logger = $this->getContainer()->get('durian.logger_manager')
                ->setUpLogger('reop.log');
        }

        $this->logger->addInfo($msg);
    }

    /**
     * 藉由csv回傳所有要交易資料
     * CSV欄位依序 userId,amount,opcode,refId,memo
     * 也可以自行在第一行定義欄位寫至csv ex: userId,amount,opcode,refId,memo
     *
     * @param string $path
     * @return array
     */
    private function getAllEntriesByCsv($path)
    {
        $csvArray = [];

        if (($handle = fopen($path, "r")) == false) {
            return $csvArray;
        }

        $row = 0;
        $userIdKey = 0;
        $amountKey = 1;
        $opcodeKey = 2;
        $refIdKey  = 3;
        $memoKey   = 4;

        $existUserId = false;
        $existAmount = false;
        $existOpcode = false;

        while (($data = fgetcsv($handle)) !== false) {

            if (count($data) < 3) {
                throw new \RuntimeException('The contents of the file is incorrect', 150170001);
            }

            $row++;

            //如第一行有定義欄位
            if ($row == 1 && preg_match("/[a-zA-z]/", $data[0])) {

                foreach (array_flip($data) as $columnName => $indexNum) {
                    $columnName = \Doctrine\Common\Util\Inflector::camelize($columnName);

                    if ($columnName == 'userId') {
                        $userIdKey = $indexNum;
                        $existUserId = true;
                    }

                    if ($columnName == 'amount') {
                        $amountKey = $indexNum;
                        $existAmount = true;
                    }

                    if ($columnName == 'opcode') {
                        $opcodeKey = $indexNum;
                        $existOpcode = true;
                    }

                    if ($columnName == 'refId') {
                        $refIdKey = $indexNum;
                    }

                    if ($columnName == 'memo') {
                        $memoKey = $indexNum;
                    }
                }

                if (!($existUserId && $existAmount && $existOpcode)) {
                    throw new \RuntimeException('The contents of the file is incorrect', 150170001);
                }

                continue;
            }

            $userId = $data[$userIdKey];
            $amount = $data[$amountKey];
            $opcode = $data[$opcodeKey];
            $refId  = $data[$refIdKey];

            $memo = '';
            if (isset($data[$memoKey])) {
                $memo = $data[$memoKey];
            }

            $csvArray[] = [
                'userId' => $userId,
                'amount' => $amount,
                'opcode' => $opcode,
                'refId' => $refId,
                'memo' => $memo
            ];
        }

        fclose($handle);

        return $csvArray;
    }

    /**
     * 交易
     *
     * @param array $data
     * @param bool $isDryRun 是否為乾跑
     *
     * @return string
     */
    private function operation($data, $isDryRun)
    {
        try {
            //userId, amount, opcode, refId, memo
            $userId = $data['userId'];
            $amount = $data['amount'];
            $opcode = $data['opcode'];
            $refId = $data['refId'];
            $memo = $data['memo'];

            if (!isset($data['opcode'])) {
                throw new \InvalidArgumentException('No opcode specified', 70004);
            }

            $isValidOpcode = $this->validator->validateOpcode($data['opcode']);
            if (!$isValidOpcode) {
                throw new \InvalidArgumentException('Invalid opcode', 70007);
            }

            $user = $this->findUser($userId);
            $userPayway = $this->getUserCash($user);
            $payway = $userPayway['entity'];

            //準備op用資料
            $options = [
                'opcode' => $opcode,
                'refId' => $refId,
                'memo' => $memo,
            ];

            $balance = '';
            $entryId = '';

            //進行op, dryRun為true的話則作查詢
            if (!$isDryRun) {
                $result = $this->doOp($payway, $amount, $options);
                $balance = $result['entry']['balance'];
                $entryId = $result['entry']['id'];
            } else {
                $result = $this->getBalance($payway);
                $balance = $result['balance'] + $amount;
            }
            unset($result['entry']['operator']);

            //準備輸出用的log
            $paywayName = $userPayway['name'];
            $username = $user->getUsername();
            $currencyOperator = $this->getContainer()->get('durian.currency');
            $currency = $currencyOperator->getMappedCode($payway->getCurrency());
            $domainConfig = $this->getEntityManager('share')->find('BBDurianBundle:DomainConfig', $user->getDomain());
            $loginCode = $domainConfig->getLoginCode();
            $name = $domainConfig->getName();

            $log = "$userId,$refId,$amount,$balance,$username,$name,$loginCode,$memo,$paywayName,$currency,$entryId\n";
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $log = "$userId,$refId,$msg,,,\n";
            unset($msg);
        }

        //unset變數以釋放記憶體
        unset($data);
        unset($userId);
        unset($refId);
        unset($amount);
        unset($balance);
        unset($username);
        unset($name);
        unset($memo);
        unset($currency);
        unset($entryId);
        unset($result);
        unset($user);
        unset($payway);
        unset($paywayName);
        unset($userPayway);
        unset($domainConfig);
        unset($loginCode);

        return $log;
    }

    /**
     * 取得使用者
     *
     * @param integer $userId 使用者ID
     * @return User
     */
    private function findUser($userId)
    {
        $user = $this->getEntityManager()->find('BBDurianBundle:User', $userId);

        if (null === $user) {
            throw new \RuntimeException('No such user', 150010029);
        }

        return $user;
    }

    /**
     * 回傳Cash 物件
     * @param User $user
     * @return mixed
     */
    private function getUserCash($user)
    {
        //兩者皆有噴例外
        if ($user->getCash() && $user->getCashFake()) {
            throw new \RuntimeException('This user has both cash and cashFake', 150170002);
        }

        //沒有cash噴例外
        if (!$user->getCash()) {
            throw new \RuntimeException('The user does not have cash', 150170003);
        }

        $payway = [];
        if ($user->getCash()) {
            $payway['entity'] = $user->getCash();
            $payway['name'] = 'cash';
        }

        return $payway;
    }

    /**
     * 進行op
     *
     * @param object $payway
     * @param float $amount
     * @param array $options
     * @return array
     */
    private function doOp($payway, $amount, $options)
    {
        $result = $this->opService->cashDirectOpByRedis($payway, $amount, $options);

        unset($payway);
        unset($amount);
        unset($options);

        return $result;
    }

    /**
     * 回傳餘額
     *
     * @param object $payway
     * @return array
     */
    private function getBalance($payway)
    {
        $result = $this->opService->getRedisCashBalance($payway);

        unset($payway);

        return $result;
    }
}
