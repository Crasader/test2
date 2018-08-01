<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Buzz\Client\Curl;
use Buzz\Message\Request;
use Buzz\Message\Response;

/**
 * 傳送入款查詢請求的背景
 */
class SendDepositTrackingRequestCommand extends ContainerAwareCommand
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
     * 紀錄目前已支援平行處理訂單查詢的支付平台
     * 待平行處理訂單查詢套接完成後移除
     *
     * @var array
     */
    private $paymentGatewayIds = [
        1, // YeePay
        5, // Allinpay
        6, // CBPay
        16, // PayEase
        21, // Shengpay
        22, // Smartpay
        23, // NewIPS
        24, // NewSmartpay
        27, // HnaPay
        33, // Tenpay
        45, // EPayLinks
        52, // KLTong
        58, // ShunShou
        61, // Unipay
        64, // NewDinPay
        67, // BooFooII99
        68, // CJBBank
        72, // NewKJBBank
        75, // Reapal
        77, // KuaiYin
        78, // UIPAS
        85, // Ehking
        87, // Khb999
        88, // MoBaoPay
        89, // NewGofPay
        90, // IPS7
        92, // WeiXin
        93, // TongHuiCard
        94, // Befpay
        95, // KKLpay
        96, // BBPay
        97, // ShangYinXin
        99, // NewIPS7
        144, // HeLiBao
        155, // XinBao
        156, // UnionPay
        157, // YeePayCard
        158, // HeYiPay
        159, // XunBao
        161, // AnFu91
        162, // KeXunPay
        163, // HaoFuPay
        164, // DuoBao
        165, // HuiHsinPay
        166, // XinYingPay
        168, // GPay
        172, // ZhihPay
        173, // GoldenPay
        174, // NewYiFuBao
        175, // NewMiaofu
        176, // ShenHui
        177, // NewShangYinXin
        178, // Amxmy
        179, // LuoBoFu
        180, // ZfbillPay
        182, // JbPay
        185, // DuoDeBao
        187, // BeeePay
        188, // SyunTongBao
        189, // WoozfPay
        190, // UPay
        191, // YuanBao
        192, // Telepay
        193, // JeanPay
        194, // ChingYiPay
        196, // Pay35
        197, // NewEPay
        200, // JuXinPay
        202, // Soulepay
        203, // YsePay
        206, // XinMaPay
        207, // ShunFoo
        208, // ZTuoPay
        210, // ZsagePay
        211, // PaySec
        216, // CRPay
        225, // ZhiTongBao
        238, // CaiMaoPay
        267, // ZhiDeBao
        273, // DeBao
        274, // WPay
        282, // WeiFuPay
        292, // JrFuHuei
        322, // YiDauYay
        361, // HuiChengFu
        387, // BeiBeiPay
        392, // YiBaoTong
        420, // YiChiFu
    ];

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
        $this->setName('durian:send-deposit-tracking-request');
        $this->setDescription('傳送入款查詢請求的背景');
        $this->addOption('start', null, InputOption::VALUE_OPTIONAL, '檢查起始時間');
        $this->addOption('end', null, InputOption::VALUE_OPTIONAL, '檢查結束時間');
        $this->setHelp(<<<EOT
檢查指定區間內的現金入款明細，傳送需做訂單查詢的請求

$ app/console durian:send-deposit-tracking-request --start="2016-01-21 11:45:00" --end="2016-01-21 17:46:00"

EOT
        );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $validator = $this->getContainer()->get('durian.validator');

        $start = $input->getOption('start');
        $end = $input->getOption('end');

        // 檢查時間格式是否正確
        if (!$validator->validateDateRange($start, $end)) {
            throw new \InvalidArgumentException('No start or end specified', 150180160);
        }
        $startAt = new \DateTime($start);
        $endAt = new \DateTime($end);

        // 初始化相關變數
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart('send-deposit-tracking-request');
        $executeCount = 0;
        $startTime = microtime(true);

        // log 記錄帶入的時間參數
        $logMessage = [
            'source' => $this->getContainer()->getParameter('kue_ip'),
            'target' => '',
            'method' => '',
            'url' => '',
            'request' => '',
            'response' => "背景開始執行。參數: Start: $start, End: $end"
        ];
        $this->log($logMessage);

        // 取得需要做訂單查詢的資料
        $trackingData = $this->getTrackingData($startAt, $endAt);

        // 傳送查詢入款資料的請求
        if (!empty($trackingData)) {
            try {
                $executeCount = $this->curlRequest($trackingData);
            } catch (\Exception $e) {
                $italkingOperator = $this->getContainer()->get('durian.italking_operator');
                $serverName = gethostname();

                $msg = sprintf(
                    '傳送查詢入款資料請求到Kue發生異常。Error: %s，Message: %s',
                    $e->getCode(),
                    $e->getMessage()
                );
                $logMessage = [
                    'source' => $serverName,
                    'target' => $this->getContainer()->getParameter('kue_ip'),
                    'method' => 'POST',
                    'url' => '/job',
                    'request' => json_encode($trackingData),
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
        }

        $bgMonitor->setMsgNum($executeCount);
        $bgMonitor->commandEnd();

        $this->printPerformance($startTime);
    }

    /**
     * 回傳需要做訂單查詢的資料
     *
     * @param \DateTime $startAt 檢查起始時間
     * @param \DateTime $endAt 檢查結束時間
     * @return array
     */
    private function getTrackingData($startAt, $endAt)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operator = $this->getContainer()->get('durian.payment_operator');
        $trackingData = [];

        // 撈出需要做訂單查詢的資料
        $qbTracking = $em->createQueryBuilder();
        $qbTracking->select('cde');
        $qbTracking->from('BBDurianBundle:CashDepositEntry', 'cde');
        $qbTracking->join(
            'BBDurianBundle:Merchant',
            'm',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'cde.merchantId = m.id'
        );
        $qbTracking->join(
            'BBDurianBundle:PaymentGateway',
            'pg',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'm.paymentGateway = pg.id'
        );
        $qbTracking->where('cde.at >= :start');
        $qbTracking->andWhere('cde.at < :end');
        $qbTracking->andWhere('cde.confirm = 0');
        $qbTracking->andWhere('pg.autoReop = 1');
        $qbTracking->andWhere($qbTracking->expr()->in('pg.id', ':paymentGatewayIds'));
        $qbTracking->setParameter('start', $startAt->format('YmdHis'));
        $qbTracking->setParameter('end', $endAt->format('YmdHis'));
        $qbTracking->setParameter('paymentGatewayIds', $this->paymentGatewayIds);
        $entries = $qbTracking->getQuery()->getResult();

        foreach ($entries as $entry) {
            $entryId = $entry->getId();

            try {
                /**
                 * 取得單筆查詢時需要的參數
                 *     1. 加上 title 背景名稱，可在 kue UI 辨認背景
                 *     2. 加上 entryId 訂單號，用於查詢解密驗證
                 *     3. 加上 verify_ip，紀錄目前查詢使用的 ip index
                 *     4. 加上 count，紀錄此 ip 查詢總次數
                 *     5. url 欄位開頭須加上 http，避免 kue 視為不合法的 url
                 */
                $entryParam = $operator->getPaymentTrackingData($entry);

                $entryParam['title'] = 'SendDepositTrackingRequestCommand';
                $entryParam['entryId'] = $entryId;
                $entryParam['attempt'] = [
                    'verify_ip' => 0,
                    'count' => 1
                ];
                $entryParam['url'] = 'http://' . $entryParam['verify_ip'][0] . $entryParam['path'];

                // 移除多餘參數
                unset($entryParam['verify_ip']);
                unset($entryParam['path']);

                $trackingData[] = [
                    'type' => 'req.payment',
                    'data' => $entryParam
                ];
            } catch (\Exception $e) {
                // 取得背景 hostname
                $serverName = gethostname();

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
            }
        }

        return $trackingData;
    }

    /**
     * 發送 curl 請求
     *
     * @param array $trackingData 訂單查詢時需要的參數
     * @return integer
     */
    private function curlRequest($trackingData)
    {
        // 取得背景 hostname
        $serverName = gethostname();

        $kueIp = $this->getContainer()->getParameter('kue_ip');
        $kueHost = $this->getContainer()->getParameter('kue_domain');

        // kue POST api 須以 JSON 格式傳送
        $request = new Request('POST');
        $request->fromUrl($kueIp . '/job');
        $request->setContent(json_encode($trackingData));
        $request->addHeader('Content-Type: application/json');
        $request->addHeader("Host: $kueHost");

        $client = new Curl();

        if ($this->client) {
            $client = $this->client;
        }

        // 關閉 curl ssl 憑證檢查
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);

        $response = new Response();

        try {
            $client->send($request, $response);
        } catch (\Exception $e) {
            throw new \RuntimeException('Kue connection failure', 150180161);
        }

        if ($this->response) {
            $response = $this->response;
        }
        $results = json_decode($response->getContent(), true);

        // 紀錄 log
        $logMessage = [
            'source' => $serverName,
            'target' => $kueIp,
            'method' => $request->getMethod(),
            'url' => $request->getResource(),
            'request' => $request->getContent(),
            'response' => $response->getContent()
        ];
        $this->log($logMessage);

        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Kue connection failure', 150180161);
        }

        // 紀錄Job 建立成功筆數
        $executeCount = 0;

        foreach ($results as $result) {
            // kue job 建立成功，會回傳 {"message":"job created","id": $jobId}
            if (!isset($result['message']) || $result['message'] != 'job created') {
                throw new \RuntimeException('Kue Job create failure', 150180162);
            }

            $executeCount++;
        }

        return $executeCount;
    }

    /**
     * 印出效能相關訊息
     *
     * @param integer $startTime
     */
    private function printPerformance($startTime)
    {
        $endTime = microtime(true);
        $excutionTime = round($endTime - $startTime, 1);
        $timeString = $excutionTime . ' sec。';

        if ($excutionTime > 60) {
            $timeString = round($excutionTime / 60, 0) . ' mins。';
        }

        // log 記錄花費的時間
        $logMessage = [
            'source' => $this->getContainer()->getParameter('kue_ip'),
            'target' => '',
            'method' => '',
            'url' => '',
            'request' => '',
            'response' => "背景執行完畢。花費時間: $timeString"
        ];
        $this->log($logMessage);
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
            $logName = 'send_deposit_tracking_request.log';
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
