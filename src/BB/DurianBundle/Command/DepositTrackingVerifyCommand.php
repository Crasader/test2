<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Buzz\Client\Curl;
use Buzz\Message\Request;
use Buzz\Message\Response;

/**
 * 入款查詢解密驗證背景
 */
class DepositTrackingVerifyCommand extends ContainerAwareCommand
{
    /**
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * @var Curl
     */
    private $client;

    /**
     * @var Response
     */
    private $response;

    /**
     * @param Response $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @param Curl $client
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
        $this->setName('durian:deposit-tracking-verify');
        $this->setDescription('入款查詢解密驗證背景');
        $this->addOption('show-stats', null, InputOption::VALUE_NONE, '回傳入款查詢解密驗證欲處理的 job 數量');
        $this->addOption('failed-from', null, InputOption::VALUE_REQUIRED, '欲處理失敗的 job 起始範圍');
        $this->addOption('failed-to', null, InputOption::VALUE_REQUIRED, '欲處理失敗的 job 結束範圍');
        $this->addOption('complete-from', null, InputOption::VALUE_REQUIRED, '欲處理成功的 job 起始範圍');
        $this->addOption('complete-to', null, InputOption::VALUE_REQUIRED, '欲處理成功的 job 結束範圍');
        $this->setHelp(<<<EOT
回傳入款查詢解密驗證欲處理的 job 數量
$ app/console durian:deposit-tracking-verify --show-stats

指定處理範圍的入款查詢解密驗證背景
$ app/console durian:deposit-tracking-verify --failed-from=0 --failed-to=999 --complete-from=0 --complete-to=999
EOT
        );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('show-stats')) {
            // 預設處理數量為 1000，避免連線失敗時造成 script 異常
            $stats = [
                'failed' => 999,
                'complete' => 999,
            ];

            try {
                $stats = $this->getKueAllJobStats();
            } catch (\Exception $e) {
                $italkingOperator = $this->getContainer()->get('durian.italking_operator');
                $serverName = gethostname();

                $msg = sprintf(
                    'Kue取得欲處理的 job 數量時異常。Error: %s，Message: %s',
                    $e->getCode(),
                    $e->getMessage()
                );
                $logMessage = [
                    'source' => $serverName,
                    'target' => $this->getContainer()->getParameter('kue_ip'),
                    'method' => '',
                    'url' => '',
                    'request' => '',
                    'response' => $msg
                ];
                $this->log($logMessage);

                // 送例外訊息至 italking
                $italkingMsg = sprintf(
                    '[%s] [%s] %s',
                    $serverName,
                    date('Y-m-d H:i:s'),
                    $msg
                );
                $italkingOperator->pushExceptionToQueue(
                    'developer_acc',
                    get_class($e),
                    $italkingMsg
                );
            }

            foreach ($stats as $stat) {
                $output->writeln($stat);
            }

            return;
        }

        list($failedFrom, $failedTo, $completeFrom, $completeTo) = $this->getInputOption($input);

        // 初始化相關變數
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart('deposit-tracking-verify');

        // 寫操作紀錄需先設定
        $this->getContainer()->set('durian.command', $this);
        $executeCount = 0;

        try {
            // 先處理 failed job，避免 retry 時剛進入 failed 的 job 又立即被重試
            if (!is_null($failedFrom)) {
                $this->getKueFailedJob($failedFrom, $failedTo);
            }

            // 紀錄此次背景確認入款筆數
            if (!is_null($completeFrom)) {
                $executeCount = $this->getKueCompleteJob($completeFrom, $completeTo);
            }
        } catch (\Exception $e) {
            $italkingOperator = $this->getContainer()->get('durian.italking_operator');
            $serverName = gethostname();

            $msg = sprintf(
                'Kue取得查詢解密驗證異常。Error: %s，Message: %s',
                $e->getCode(),
                $e->getMessage()
            );
            $logMessage = [
                'source' => $serverName,
                'target' => $this->getContainer()->getParameter('kue_ip'),
                'method' => '',
                'url' => '',
                'request' => '',
                'response' => $msg
            ];
            $this->log($logMessage);

            // 送例外訊息至 italking
            $italkingMsg = sprintf(
                '[%s] [%s] %s',
                $serverName,
                date('Y-m-d H:i:s'),
                $msg
            );
            $italkingOperator->pushExceptionToQueue(
                'developer_acc',
                get_class($e),
                $italkingMsg
            );
        }

        $bgMonitor->setMsgNum($executeCount);
        $bgMonitor->commandEnd();
    }

    /**
     * 回傳Entity Manager
     *
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * 回傳入款查詢解密驗證欲處理的 job 數量
     *
     * @return array
     */
    private function getKueAllJobStats()
    {
        // 取得背景 hostname 及 kue ip
        $serverName = gethostname();
        $kueIp = $this->getContainer()->getParameter('kue_ip');

        $stats = [
            'failed' => 0,
            'complete' => 0,
        ];

        foreach (array_keys($stats) as $type) {
            $curlParam = [
                'uri' => "/jobs/req.payment/{$type}/stats",
                'method' => 'GET',
            ];
            $results = $this->curlRequest($curlParam);

            // 紀錄 curl log
            $logMessage = [
                'source' => $serverName,
                'target' => $kueIp,
                'method' => $curlParam['method'],
                'url' => $curlParam['uri'],
                'request' => '',
                'response' => json_encode($results),
            ];
            $this->log($logMessage);

            $stats[$type] = $results['count'];
        }

        return $stats;
    }

    /**
     * 回傳背景輸入的參數
     *
     * @param InputInterface $input 背景輸入實例
     * @return array
     */
    private function getInputOption(InputInterface $input)
    {
        $validator = $this->getContainer()->get('durian.validator');

        $failedFrom = $input->getOption('failed-from');
        $failedTo = $input->getOption('failed-to');
        $completeFrom = $input->getOption('complete-from');
        $completeTo = $input->getOption('complete-to');

        if (!is_null($failedFrom) || !is_null($failedTo)) {
            if (!$validator->isInt($failedFrom, true) || !$validator->isInt($failedTo, true)) {
                throw new \InvalidArgumentException('Illegal failed-from or failed-to', 150180206);
            }
        }

        if (!is_null($completeFrom) || !is_null($completeTo)) {
            if (!$validator->isInt($completeFrom, true) || !$validator->isInt($completeTo, true)) {
                throw new \InvalidArgumentException('Illegal complete-from or complete-to', 150180207);
            }
        }

        return [
            $failedFrom,
            $failedTo,
            $completeFrom,
            $completeTo,
        ];
    }

    /**
     * 處理 kue Fail 的訂單查詢
     *
     * @param integer $failedFrom 欲處理失敗的 job 起始範圍
     * @param integer $failedTo 欲處理失敗的 job 結束範圍
     */
    private function getKueFailedJob($failedFrom, $failedTo)
    {
        // 取得背景 hostname 及 kue ip
        $serverName = gethostname();
        $kueIp = $this->getContainer()->getParameter('kue_ip');

        // 取得指定範圍內失敗的訂單查詢結果
        $curlParam = [
            'uri' => "/jobs/req.payment/failed/{$failedFrom}..{$failedTo}/asc",
            'method' => 'GET'
        ];
        $results = $this->curlRequest($curlParam);

        // 紀錄 curl log
        $logMessage = [
            'source' => $serverName,
            'target' => $kueIp,
            'method' => $curlParam['method'],
            'url' => $curlParam['uri'],
            'request' => '',
            'response' => json_encode($results)
        ];
        $this->log($logMessage);

        foreach ($results as $result) {
            $entryId = $result['data']['entryId'];
            $request = [];

            /**
             * 依照對外的格式，取出對應的訂單查詢參數
             *     1. GET 傳送的參數已串在 url 後方，不另外取出
             *     2. POST 傳送的參數在 form 欄位
             *     3. JSON 傳送的參數在 json 欄位
             *     4. SOAP 傳送的參數在 arguments 欄位
             */
            if (isset($result['data']['form'])) {
                $request = $result['data']['form'];
            }

            if (isset($result['data']['json'])) {
                $request = $result['data']['json'];
            }

            // soap 補上 method，讓 log 紀錄是 soap
            if (isset($result['data']['arguments'])) {
                $request = $result['data']['arguments'];
                $result['data']['method'] = 'SOAP';
            }

            // 紀錄 Fail 原因
            $failMsg = "Kue訂單查詢失敗，Entry Id: {$entryId}。Error Message: {$result['error']}";
            $logMessage = [
                'source' => $kueIp,
                'target' => $result['data']['headers']['Host'],
                'method' => $result['data']['method'],
                'url' => $result['data']['url'],
                'request' => json_encode($request),
                'response' => $failMsg
            ];
            $this->log($logMessage);

            // 執行重試
            $attempt = $result['data']['attempt'];
            $this->sendDepositTrackingRequest($entryId, $attempt);

            /**
             * 確認入款成功、取得查詢參數失敗、超過重試次數、重試成功都需刪除原本的 Job
             * 若新增 Job 連線失敗，對外時會丟出例外，不能刪除 Job
             */
            $this->deleteKueJob($result['id']);
        }
    }

    /**
     * 處理 kue Complete 的訂單查詢
     *
     * @param integer $completeFrom 欲處理成功的 job 起始範圍
     * @param integer $completeTo 欲處理成功的 job 結束範圍
     * @return integer
     */
    private function getKueCompleteJob($completeFrom, $completeTo)
    {
        // 取得背景 hostname 及 kue ip
        $serverName = gethostname();
        $kueIp = $this->getContainer()->getParameter('kue_ip');

        $em = $this->getEntityManager();
        $operator = $this->getContainer()->get('durian.payment_operator');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');

        // 紀錄此次確認入款筆數
        $executeCount = 0;

        // 取得指定範圍內完成的訂單查詢結果
        $curlParam = [
            'uri' => "/jobs/req.payment/complete/{$completeFrom}..{$completeTo}/asc",
            'method' => 'GET'
        ];
        $results = $this->curlRequest($curlParam);

        // 先將對外結果解碼，避免亂碼無法 json_encode 寫入 log
        $results = $this->decodeKueJobResult($results);

        // 紀錄 curl log
        $logMessage = [
            'source' => $serverName,
            'target' => $kueIp,
            'method' => $curlParam['method'],
            'url' => $curlParam['uri'],
            'request' => '',
            'response' => json_encode($results)
        ];
        $this->log($logMessage);

        foreach ($results as $result) {
            $entryId = $result['data']['entryId'];
            $depositEntry = $cdeRepo->findOneBy(['id' => $entryId]);

            // 若訂單已 confirm，直接刪除 Job
            if ($depositEntry->isConfirm()) {
                $this->deleteKueJob($result['id']);

                continue;
            }

            $request = [];

            /**
             * 依照對外的格式，取出對應的訂單查詢參數
             *     1. GET 傳送的參數已串在 url 後方，不另外取出
             *     2. POST 傳送的參數在 form 欄位
             *     3. JSON 傳送的參數在 json 欄位
             *     4. SOAP 傳送的參數在 arguments 欄位
             */
            if (isset($result['data']['form'])) {
                $request = $result['data']['form'];
            }

            if (isset($result['data']['json'])) {
                $request = $result['data']['json'];
            }

            // soap 補上 method，讓 log 紀錄是 soap
            if (isset($result['data']['arguments'])) {
                $request = $result['data']['arguments'];
                $result['data']['method'] = 'SOAP';
            }

            try {
                // 支付平台返回放入 content 欄位，做查詢解密驗證
                $sourceData = ['content' => $result['data']['job_result']['body']];
                $operator->depositExamineVerify($depositEntry, $sourceData);

                // 訂單查詢成功，執行確認入款
                $operator->depositConfirm($depositEntry);
                $executeCount++;

                // 紀錄解密驗證成功的 log
                $successMsg = sprintf(
                    '解密驗證成功，Entry Id: %s。%s"',
                    $entryId,
                    json_encode($result['data']['job_result'])
                );
                $logMessage = [
                    'source' => $kueIp,
                    'target' => $result['data']['headers']['Host'],
                    'method' => $result['data']['method'],
                    'url' => $result['data']['url'],
                    'request' => json_encode($request),
                    'response' => $successMsg
                ];
                $this->log($logMessage);
            } catch (\Exception $e) {
                $msg = sprintf(
                    '查詢解密驗證失敗，Entry Id: %s，Error Message: %s。%s',
                    $entryId,
                    $e->getMessage(),
                    json_encode($result['data']['job_result'])
                );
                $logMessage = [
                    'source' => $kueIp,
                    'target' => $result['data']['headers']['Host'],
                    'method' => $result['data']['method'],
                    'url' => $result['data']['url'],
                    'request' => json_encode($request),
                    'response' => $msg
                ];
                $this->log($logMessage);

                // 執行重試
                $attempt = $result['data']['attempt'];
                $this->sendDepositTrackingRequest($entryId, $attempt);
            }

            /**
             * 確認入款成功、取得查詢參數失敗、超過重試次數、重試成功都需刪除原本的 Job
             * 若新增 Job 連線失敗，對外時會丟出例外，不能刪除 Job
             */
            $this->deleteKueJob($result['id']);
        }

        return $executeCount;
    }

    /**
     * 發送 curl 請求
     *
     * @param array $curlParam curl 的相關參數
     *    支援的參數：
     *        uri string
     *        method string
     *        param array
     * @return array
     */
    private function curlRequest($curlParam)
    {
        // 取得背景 hostname
        $serverName = gethostname();

        $kueIp = $this->getContainer()->getParameter('kue_ip');
        $kueHost = $this->getContainer()->getParameter('kue_domain');

        // kue POST api 須以 JSON 格式傳送
        $request = new Request($curlParam['method']);
        $request->fromUrl($kueIp . $curlParam['uri']);
        $request->addHeader('Content-Type: application/json');
        $request->addHeader("Host: $kueHost");

        if ($curlParam['method'] === 'POST') {
            $request->setContent(json_encode($curlParam['param']));
        }

        $client = new Curl();

        if ($this->client) {
            $client = $this->client;
        }

        // 關閉 curl ssl 憑證檢查
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);

        $response = new Response();
        $client->send($request, $response);

        if ($this->response) {
            $response = $this->response;
        }
        $results = json_decode($response->getContent(), true);

        if ($response->getStatusCode() != 200) {
            // 紀錄 log
            $logMessage = [
                'source' => $serverName,
                'target' => $kueIp,
                'method' => $request->getMethod(),
                'url' => $request->getResource(),
                'request' => $request->getContent(),
                'response' => json_encode($results)
            ];
            $this->log($logMessage);

            throw new \RuntimeException('Kue connection failure', 150180161);
        }

        // 檢查 kue 返回是否有 error
        if (isset($results['error'])) {
            // 因平行解密驗證有可能刪除相同的 Job，因此刪除時若 Job 不存在，不需要丟例外
            if ($curlParam['method'] != 'DELETE' || substr($results['error'], -12) != 'doesnt exist') {
                // 紀錄 log
                $logMessage = [
                    'source' => $serverName,
                    'target' => $kueIp,
                    'method' => $request->getMethod(),
                    'url' => $request->getResource(),
                    'request' => $request->getContent(),
                    'response' => json_encode($results)
                ];
                $this->log($logMessage);

                throw new \RuntimeException('Kue return error message', 150180163);
            }
        }

        return $results;
    }

    /**
     * 傳送訂單查詢請求到 kue
     *
     * @param integer $entryId 訂單Id
     * @param array $attempt 重試相關參數
     * @return
     */
    private function sendDepositTrackingRequest($entryId, $attempt)
    {
        // 取得背景 hostname 及 kue ip
        $serverName = gethostname();
        $kueIp = $this->getContainer()->getParameter('kue_ip');

        $em = $this->getEntityManager();
        $operator = $this->getContainer()->get('durian.payment_operator');

        $entry = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => $entryId]);

        try {
            $attemptCount = $attempt['count'] + 1;
            $attemptIp = $attempt['verify_ip'];

            // 當查詢總次數超過 3 次，使用下一個 IP 重試
            if ($attemptCount > 3) {
                $attemptCount = 1;
                $attemptIp++;
            }

            /**
             * 取得單筆查詢時需要的參數
             *     1. 加上 title 背景名稱，可在 kue UI 辨認背景
             *     2. 加上 entryId 訂單號，用於查詢解密驗證
             *     3. 累加 verify_ip，紀錄目前查詢使用的 ip index，每個 ip 只能重試 3 次
             *     4. 累加 count，紀錄此 ip 查詢總次數
             *     5. url 欄位開頭須加上 http，避免 kue 視為不合法的 url
             */
            $entryParam = $operator->getPaymentTrackingData($entry);

            // 當所有 IP 皆重試過，不再重試
            if (!isset($entryParam['verify_ip'][$attemptIp])) {
                return;
            }

            $entryParam['title'] = 'DepositTrackingVerifyCommand';
            $entryParam['entryId'] = $entryId;
            $entryParam['attempt'] = [
                'verify_ip' => $attemptIp,
                'count' => $attemptCount
            ];
            $entryParam['url'] = 'http://' . $entryParam['verify_ip'][$attemptIp] . $entryParam['path'];

            // 移除多餘參數
            unset($entryParam['verify_ip']);
            unset($entryParam['path']);
        } catch (\Exception $e) {
            $msg = sprintf(
                '取得查詢參數錯誤，Entry Id: %s。Error: %s，Message: %s',
                $entryId,
                $e->getCode(),
                $e->getMessage()
            );
            $logMessage = [
                'source' => $serverName,
                'target' => '',
                'method' => '',
                'url' => '',
                'request' => '',
                'response' => $msg
            ];
            $this->log($logMessage);

            return;
        }

        // 新增訂單查詢的 Job
        $curlParam = [
            'uri' => '/job',
            'method' => 'POST',
            'param' => [
                'type' => 'req.payment',
                'data' => $entryParam
            ]
        ];
        $results = $this->curlRequest($curlParam);

        // 紀錄 curl log
        $logMessage = [
            'source' => $serverName,
            'target' => $kueIp,
            'method' => $curlParam['method'],
            'url' => $curlParam['uri'],
            'request' => json_encode($curlParam['param']),
            'response' => json_encode($results)
        ];
        $this->log($logMessage);
    }

    /**
     * 刪除 kue Job
     *
     * @param integer $jobId kue Job Id
     */
    private function deleteKueJob($jobId)
    {
        // 取得背景 hostname 及 kue ip
        $serverName = gethostname();
        $kueIp = $this->getContainer()->getParameter('kue_ip');

        $curlParam = [
            'uri' => "/job/{$jobId}",
            'method' => 'DELETE'
        ];
        $results = $this->curlRequest($curlParam);

        // 紀錄 curl log
        $logMessage = [
            'source' => $serverName,
            'target' => $kueIp,
            'method' => $curlParam['method'],
            'url' => $curlParam['uri'],
            'request' => '',
            'response' => json_encode($results)
        ];
        $this->log($logMessage);
    }

    /**
     * 將 kue 對外的結果解碼
     *
     * @param array $results kue 對外的結果
     * @return array
     */
    private function decodeKueJobResult($results)
    {
        $em = $this->getEntityManager();
        $operator = $this->getContainer()->get('durian.payment_operator');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');

        // kue 會將支付平台返回值存入 job_result 的 body 中，需先解碼，避免亂碼無法 json_encode 寫入 log
        foreach ($results as $index => $result) {
            $entryId = $result['data']['entryId'];
            $depositEntry = $cdeRepo->findOneBy(['id' => $entryId]);

            $jobResult = $operator->processTrackingResponseEncoding($depositEntry, $result['data']['job_result']);
            $results[$index]['data']['job_result'] = $jobResult;
        }

        return $results;
    }

    /**
     * 記錄 log
     *
     * @param array $msg 需要記錄的參數，共有以下參數：
     *     source   發送 curl 或執行背景的 hostname 或 ip
     *     target   curl 目標 ip
     *     method   提交方式
     *     url      提交 url
     *     request  提交的參數
     *     response 返回結果或錯誤訊息
     */
    private function log($msg)
    {
        // 設定 logger
        if (is_null($this->logger)) {
            $logName = 'deposit_tracking_verify.log';
            $this->logger = $this->getContainer()->get('durian.logger_manager')->setUpLogger($logName);
        }

        $logContent = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $msg['source'],
            $msg['target'],
            $msg['method'],
            $msg['url'],
            $msg['request'],
            $msg['response']
        );
        $this->logger->addInfo($logContent);
    }
}
