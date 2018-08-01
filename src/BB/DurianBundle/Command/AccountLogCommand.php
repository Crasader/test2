<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\AccountLog;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response;
use Buzz\Client\Curl;
use Buzz\Listener\LoggerListener;

/**
 * 發request 到account系統 (一次100筆)
 * 執行op app/console durian:toAccont
 */
class AccountLogCommand extends ContainerAwareCommand
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
     * @var \Buzz\Message\Response
     */
    private $response;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * @param \Buzz\Message\Response $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @param \Buzz\Client\Curl $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:toAccount')
            ->setDescription('送出款至Account紀錄')
            ->addOption('disable-log', null, InputOption::VALUE_NONE, 'Disable logging')
            ->setHelp(<<<EOT
送出款至Account紀錄
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

        $container = $this->getContainer();

        $bgMonitor = $container->get('durian.monitor.background');
        $bgMonitor->commandStart('toAccount');

        $em = $container->get('doctrine.orm.entity_manager');

        $this->setUpLogger();
        $this->disableLog = $this->input->getOption('disable-log');

        $this->log("AccountLogCommand Start.");

        // 撈出未處理AccountLog Entity
        $qb = $em->createQueryBuilder();
        $qb->select('log')
            ->from('BBDurianBundle:AccountLog', 'log')
            ->andWhere("log.status = :status")
            ->setParameter('status', AccountLog::UNTREATED)
            ->andWhere("log.count < :count")
            ->setParameter('count', 3)
            ->setFirstResult(0)
            ->setMaxResults(100);

        $accountLogs = $qb->getQuery()->getResult();

        foreach ($accountLogs as $accountLog) {
            $this->requestAccountLog($accountLog);
        }

        $em->flush();

        $this->log("AccountLogCommand finish.");

        $handler = $this->logger->popHandler();
        $handler->close();

        $bgMonitor->setMsgNum(count($accountLogs));
        $bgMonitor->commandEnd();
    }

    /**
     * 設定logger
     */
    private function setUpLogger()
    {
        $this->logger = $this->getContainer()->get('durian.logger_manager')->setUpLogger('AccountLog.log');
    }

    /**
     * 記錄log
     *
     * @param String $message
     */
    private function log($msg)
    {
        if ($this->disableLog) {
            return;
        }

        $this->logger->addInfo($msg);
    }

    /**
     * 發送出款資訊到帳號出款系統
     *
     * 只有直營網才需要進帳號系統
     *
     * @param AccountLog $accountLog
     */
    private function requestAccountLog(AccountLog $accountLog)
    {
        $container = $this->getContainer();
        $italkingOperator = $container->get('durian.italking_operator');
        $server = gethostname();
        $host = $container->getParameter('account_domain');
        $parseIp = parse_url($container->getParameter('account_ip'));
        $ip = $parseIp['host'];

        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $cweRepo = $em->getRepository('BBDurianBundle:CashWithdrawEntry');

        $parameters = $this->processAccountParameters($accountLog);

        $logger = $this->setUpRequestLogger();
        $logStr = 'id:' . $accountLog->getId();

        try {
            // 連到accout的出款api送出款資料
            $client = new Curl();

            if ($this->client) {
                $client = $this->client;
            }

            // url: http://account.cnnbet.net/app/tellership/auto_pay_detail.php
            $request = new FormRequest('GET', '/app/tellership/auto_pay_detail.php', $ip);
            $request->addFields($parameters);
            $request->addHeader("Host: {$host}");

            // 關閉curl ssl憑證檢查
            $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
            $client->setOption(CURLOPT_SSL_VERIFYPEER, false);

            $response = new Response();

            $listener = new LoggerListener([$logger, 'addDebug']);
            $listener->preSend($request);

            $client->send($request, $response);

            $listener->postSend($request, $response);

            if ($this->response) {
                $response = $this->response;
            }

            $logger->addDebug($request . $response);

            $result = json_decode($response->getContent());

            if ($response->getStatusCode() == 200) {
                if ($result->result == 'success' || $result->result == 'duplicate') {
                    $accountLog->setStatus(AccountLog::SENT);
                }
            }
            $accountLog->addCount();

            $logger->popHandler()->close();

            if ($accountLog->getStatus() == AccountLog::SENT) {
                $logStr .= " Success ";
            } else {
                $logStr .= " Fail ";
            }
        } catch (\Exception $e) {
            $code = $e->getCode();
            $logStr .= " Error $code";
            $logStr .= " Msg: {$e->getMessage()}";
            $accountLog->addCount();
        }

        $this->log($logStr);

        $isOverThree = $accountLog->getCount() >= 3;
        $isUntreated = $accountLog->getStatus() == AccountLog::UNTREATED;
        if ($isOverThree && $isUntreated) {
            // 從對應的cashWithdrawEntry取domain判斷要給esball還是博九
            $entry = $cweRepo->findOneBy(['id' => $accountLog->getFromId()]);
            $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', $entry->getDomain());
            $domainAlias = $domainConfig->getName();
            $loginCode = $domainConfig->getLoginCode();
            $now = date('Y-m-d H:i:s');

            $msg = '出款傳送至Account失敗，請至現金記錄(ACC記錄)重送';
            $notice = "如發生多筆，請聯絡DC-OP-維護組檢查 $ip 到 $host 的線路及Account機器是否正常";

            if ($result && ($result->result !== 'success' || $result->result !== 'duplicate')) {
                $msg = "Account回傳訊息異常，訊息為: $result->result";
                $notice = '請通知RD2-應用技術部-Nate及RD5-電子商務部檢查';
            }

            $queueMsg = sprintf(
                '[%s] [%s] %s@%s, ID: %s, %s。%s。',
                $server,
                $now,
                $domainAlias,
                $loginCode,
                $accountLog->getFromId(),
                $msg,
                $notice
            );

            // 送至GM的italking訊息
            $gmMsg = "出款傳送至Account失敗\n";
            $gmMsg .= "① 請客服測試登入 $host 是否正常。如果無法正常登入，請致電 RD2-應用技術部-Nate 上線查看。\n";
            $gmMsg .= "② 如果正常，請聯絡DC-OP-維護組檢查 $ip 到 $host 的線路及Account機器是否正常";

            $queueGmMsg = sprintf(
                '[%s] [%s] %s@%s, ID: %s, %s。',
                $server,
                $now,
                $domainAlias,
                $loginCode,
                $accountLog->getFromId(),
                $gmMsg
            );

            // 送訊息至GM的italking
            $italkingOperator->pushMessageToQueue('account_fail', $queueGmMsg);

            if ($entry->getDomain() == 6) {
                $italkingOperator->pushMessageToQueue('account_fail', $queueMsg, 6);
            }

            if ($entry->getDomain() == 98) {
                $italkingOperator->pushMessageToQueue('account_fail', $queueMsg, 98);
            }

            // kresball
            if ($entry->getDomain() == 3820175) {
                $italkingOperator->pushMessageToQueue('account_fail_kr', $queueMsg, 3820175);
            }

            // esball global
            if ($entry->getDomain() == 3819935) {
                $italkingOperator->pushMessageToQueue('account_fail', $queueMsg, 3819935);
            }

            // eslot
            if ($entry->getDomain() == 3820190) {
                $italkingOperator->pushMessageToQueue('account_fail', $queueMsg, 3820190);
            }
        }
    }

    /**
     * 整理到帳戶系統參數
     *
     * @param AccountLog $accountLog
     * @return array
     */
    private function processAccountParameters(AccountLog $accountLog)
    {
        $currencyOperator = $this->getContainer()->get('durian.currency');
        $accDate = $accountLog->getAccountDate();

        //轉換成美東時間
        $usETimeZone = new \DateTimeZone('Etc/GMT+4');
        $accDate->setTimezone($usETimeZone);

        $parameters = [
            'uitype' => 'auto',
            'CURRENCY_NAME' => $currencyOperator->getMappedCode($accountLog->getCurrencyName()),
            'ACCOUNT' => $accountLog->getAccount(),
            'WEB' => $accountLog->getWeb(),
            'ACCOUNT_DATE' => substr($accDate->format('Y-m-d H:i:s'), 0, 10),
            'ACCOUNT_NAME' => $accountLog->getAccountName(),
            'NAME_REAL' => $accountLog->getNameReal(),
            'ACCOUNT_NO' => $accountLog->getAccountNo(),
            'branch' => $accountLog->getBranch(),
            'BANK_NAME' => $accountLog->getBankName(),
            'GOLD' => $accountLog->getGold(),
            'CHECK02' => $accountLog->getCheck02(),
            'MONEY01' => $accountLog->getMoney01(),
            'MONEY02' => $accountLog->getMoney02(),
            'MONEY03' => $accountLog->getMoney03(),
            'STATUS_STR' => $accountLog->getStatusStr(),
            'FROMID' => $accountLog->getFromId(),
            'PREVIOUS_ID' => $accountLog->getPreviousId(),
            'REMARK' => $accountLog->getRemark(),
            'IS_TEST' => (int) $accountLog->isTest(),
            'DETAIL_MODIFIED' => (int) $accountLog->isDetailModified(),
            'NON_PERSONAL_ACCOUNT' => 1,
            'multipleAudit' => $accountLog->getMultipleAudit(),
            'domain' => $accountLog->getDomain(),
            'member_level_id' => $accountLog->getLevelId(),
        ];

        // 如果沒有ACCOUNT_NAME，代表不支援非本人帳戶功能
        if (!$parameters['ACCOUNT_NAME']) {
            $parameters['ACCOUNT_NAME'] = $parameters['NAME_REAL'];
            $parameters['NON_PERSONAL_ACCOUNT'] = 0;
        }

        return $parameters;
    }

    /**
     * 設定request logger
     *
     * @return Logger
     */
    private function setUpRequestLogger()
    {
        $logger = $this->getContainer()->get('durian.logger_manager')->setUpLogger('account/toAcc.log');

        return $logger;
    }
}
