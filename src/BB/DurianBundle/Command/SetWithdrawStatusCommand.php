<?php

namespace BB\DurianBundle\Command;

use BB\DurianBundle\Entity\CashWithdrawEntry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Buzz\Client\Curl;
use Buzz\Message\Response;
use Buzz\Message\Form\FormRequest;

/**
 * 傳送出款請求的背景
 */
class SetWithdrawStatusCommand extends ContainerAwareCommand
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
        $this->setName('durian:set-withdraw-status');
        $this->setDescription('傳送出款請求的背景');
        $this->addOption('recover-fail', null, InputOption::VALUE_NONE, '執行failed queue重送');
        $this->setHelp(
            <<<EOT
執行修改set_withdraw_status_queue、set_withdraw_status_queue_retry內出款明細狀態
$ app/console durian:set-withdraw-status

執行修改set_withdraw_status_queue_failed內出款明細狀態
$ app/console durian:set-withdraw-status --recover-fail
EOT
        );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // 初始化相關變數
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart('set-withdraw-status');

        $executeCount = 0;
        if ($input->getOption('recover-fail')) {
            $executeCount += $this->setWithdrawStatus('set_withdraw_status_queue_failed');
        } else {
            $executeCount += $this->setWithdrawStatus('set_withdraw_status_queue_retry');
            $executeCount += $this->setWithdrawStatus('set_withdraw_status_queue');
        }

        $bgMonitor->setMsgNum($executeCount);
        $bgMonitor->commandEnd();
    }

    /**
     * 執行提交出款
     *
     * @param string $queue 要修改狀態的Queue名稱
     */
    public function setWithdrawStatus($queue)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $cweRepo = $em->getRepository('BBDurianBundle:CashWithdrawEntry');
        $count = 0;

        while ($count < 100) {
            $withdrawData = json_decode($redis->lpop($queue), true);

            if (!$withdrawData) {
                break;
            }

            // 如果是retry的次數要+1
            if (isset($withdrawData['retry'])) {
                $withdrawData['retry'] += 1;
            }

            try {
                $withdrawEntry = $cweRepo->findOneBy(['id' => $withdrawData['entry_id']]);

                if (!$withdrawEntry) {
                    throw new \RuntimeException('No such withdraw entry', 380001);
                }

                if ($withdrawEntry->getStatus() != CashWithdrawEntry::SYSTEM_LOCK) {
                    throw new \RuntimeException('Withdraw status not system lock', 150380046);
                }

                if ($withdrawData['status'] == CashWithdrawEntry::CONFIRM) {
                    $this->cashWithdrawEntryConfirm($withdrawEntry);
                }

                if ($withdrawData['status'] == CashWithdrawEntry::PROCESSING) {
                    $withdrawEntry->setStatus($withdrawData['status']);
                    $em->flush();
                }

                $msg = sprintf(
                    '出款狀態已修改，CashWithdrawEntry Id: %s, status:%s=>%s',
                    $withdrawData['entry_id'],
                    CashWithdrawEntry::SYSTEM_LOCK,
                    $withdrawData['status']
                );

                $this->log($msg);
            } catch (\Exception $e) {
                $msg = sprintf(
                    '出款狀態修改失敗，CashWithdrawEntry Id: %s。Error: %s，Message: %s',
                    $withdrawData['entry_id'],
                    $e->getCode(),
                    $e->getMessage()
                );

                $this->log($msg);

                // 避免同分秒，sleep 10後再繼續執行
                sleep(10);

                // 第一次送沒有retry，要補上retry
                if (!isset($withdrawData['retry'])) {
                    $withdrawData['retry'] = 0;
                }

                // 如果重試超過10次推到failed queue，否則推到retry queue
                if ($withdrawData['retry'] >= 10) {
                    $redis->rpush('set_withdraw_status_queue_failed', json_encode($withdrawData));
                } else {
                    $redis->rpush('set_withdraw_status_queue_retry', json_encode($withdrawData));
                }
            }
            ++$count;
        }

        return $count;
    }

    /**
     * 出款明細變更為確認
     *
     * @param CashWithdrawEntry $withdrawEntry 線上支付明細
     */
    private function cashWithdrawEntryConfirm(CashWithdrawEntry $withdrawEntry)
    {
        $id = $withdrawEntry->getId();
        $uri = "/api/cash/withdraw/{$id}";
        $data = [
            'checked_username' => $withdrawEntry->getCheckedUsername(),
            'merchant_withdraw_id' => $withdrawEntry->getMerchantWithdrawId(),
            'status' => CashWithdrawEntry::CONFIRM,
            'manual' => '1',
            'system' => '1',
        ];

        $curlParam = [
            'uri' => $uri,
            'data' => $data,
        ];

        $response = $this->curlRequest($curlParam);

        if ($response['result'] !== 'ok') {
            throw new \RuntimeException($response['msg'], $response['code']);
        }
    }

    /**
     * 呼叫API
     *
     * @param array $curlParam 參數說明如下
     *     string uri
     *     array data
     *
     * @return array
     */
    private function curlRequest($curlParam)
    {
        $ip = $this->getContainer()->getParameter('rd5_ip');
        $host = $this->getContainer()->getParameter('rd5_domain');

        $client = new Curl();
        $response = new Response();

        $data = isset($curlParam['data']) ? $curlParam['data'] : [];

        $curlRequest = new FormRequest('PUT', $curlParam['uri'], $ip);
        $curlRequest->addFields($data);
        $curlRequest->addHeader('Host: ' . $host);

        if ($this->client) {
            $client = $this->client;
        }

        $client->setOption(CURLOPT_TIMEOUT, 30);
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOPtion(CURLOPT_SSL_VERIFYPEER, false);
        $client->send($curlRequest, $response);

        if ($this->response) {
            $response = $this->response;
        }

        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Durian api call failed', 150380042);
        }

        return json_decode($response->getContent(), true);
    }

    /**
     * 記錄 log
     *
     * @param array $msg 記錄訊息
     */
    private function log($msg)
    {
        // 設定 logger
        if (is_null($this->logger)) {
            $logName = 'set_withdraw_status.log';
            $this->logger = $this->getContainer()->get('durian.logger_manager')->setUpLogger($logName);
        }

        $logContent = sprintf(
            '%s %s"',
            gethostname(),
            $msg
        );
        $this->logger->addInfo($logContent);
    }
}
