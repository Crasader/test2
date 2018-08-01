<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Buzz\Message\Form\FormRequest;
use Buzz\Client\Curl;
use Buzz\Message\Response;

/**
 * 通知稽核背景
 */
class AuditCommand extends ContainerAwareCommand
{
    /**
     * @var \Buzz\Client\Curl
     */
    protected $client;

    /**
     * @var \Buzz\Message\Response
     */
    protected $response;

    /**
     * @param \Buzz\Client\Curl
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @param \Buzz\Message\Response $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:audit')
            ->setDescription('通知稽核背景')
            ->setHelp(<<<EOT
發送需要通知稽核的資料
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $count = 0;
        $params = [];

        $bgMonitor->commandStart('audit');

        try {
            // 最多一次送1000筆
            while ($count < 1000) {
                $queueMsg = json_decode($redis->rpop('audit_queue'), true);

                if (!$queueMsg) {
                    break;
                }

                $params[] = $queueMsg;
                ++$count;
            }

            // 如果有資料就要通知稽核
            if ($params) {
                $this->audit($params);
            }
        } catch (\Exception $e) {
            // 如果例外發生就把資料推回queue
            foreach (array_reverse($params) as $pushMsg) {
                if (!isset($pushMsg['retry'])) {
                    $pushMsg['retry'] = 0;
                }

                $pushMsg['retry'] += 1;

                if ($pushMsg['retry'] >= 10) {
                    $pushMsg['retry'] = 0;
                }

                $redis->rpush('audit_queue', json_encode($pushMsg));
            }
        }

        $bgMonitor->setMsgNum($count);
        $bgMonitor->commandEnd();
    }

    /**
     * 進行通知稽核
     *
     * @param array $params
     */
    private function audit($params)
    {
        $ip = $this->getContainer()->getParameter('audit_ip');
        $host = $this->getContainer()->getParameter('audit_domain');

        $request = new FormRequest('POST', '/api/payment/audit/post.json', $ip);
        $request->addFields(['Audit' => json_encode($params)]);
        $request->addHeader("Host: $host");

        $client = new Curl();

        if ($this->client) {
            $client = $this->client;
        }

        $response = new Response();

        if ($this->response) {
            $response = $this->response;
        }

        $result = '';

        try {
            $client->send($request, $response);

            if ($response->getStatusCode() != 200) {
                throw new \RuntimeException(
                    'StatusCode: ' . $response->getStatusCode() . '，ErrorMsg: ' . $response->getContent(),
                    370052
                );
            }
        } catch (\Exception $e) {
             // 發生例外則發送至iTalking
            $italkingOperator = $this->getContainer()->get('durian.italking_operator');
            $server = gethostname();

            $entry = $params[0];
            if (isset($entry['retry']) && $entry['retry'] >= 9) {
                $msg = sprintf(
                    "通知稽核失敗，請聯絡DC-OP-維護組檢查 %s 到 %s 的線路及機器是否正常。" .
                    "若DC-OP-維護組檢查正常，請通知 RD5-電子商務部 上線查看。\n測試語法如下：" .
                    "curl -X POST 'http://%s/check.html' -H'host: %s'",
                    $server,
                    $ip,
                    $ip,
                    $host
                );

                $italkingOperator->pushExceptionToQueue(
                    'acc_system',
                    get_class($e),
                    $msg
                );
            }

            $result = sprintf(
                "ErrorCode: %s，ErrorMsg: %s",
                $e->getCode(),
                $e->getMessage()
            );
        }

        if (!$result) {
            $result = $response->getContent();
        }

        $logContent = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $ip,
            '127.0.0.1',
            $request->getMethod(),
            $request->getResource(),
            $request->getContent(),
            $result
        );

        $logger = $this->getContainer()->get('durian.logger_manager')->setUpLogger('audit.log');
        $logger->addInfo($logContent);
        $logger->popHandler()->close();

        $ret = json_decode($result, true);

        if (!isset($ret['result']) || $ret['result'] != 'ok') {
            throw new \RuntimeException("通知稽核返回訊息異常： $result");
        }
    }
}
