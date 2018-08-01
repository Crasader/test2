<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Buzz\Message\Form\FormRequest;
use Buzz\Client\Curl;
use Buzz\Message\Response;

/**
 * 發送購物網通知
 */
class ShopwebCommand extends ContainerAwareCommand
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
            ->setName('durian:shop-web')
            ->setDescription('發送購物網通知')
            ->setHelp(<<<EOT
發送購物網通知
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
        $shopWebInfo = [];

        $bgMonitor->commandStart('shop-web');

        try {
            // 最多一次送1000筆
            while ($count < 1000) {
                $shopWebInfo = json_decode($redis->rpop('shopweb_queue'), true);

                if (!$shopWebInfo) {
                    break;
                }

                $this->shopWeb($shopWebInfo);
                ++$count;
            }
        } catch (\Exception $e) {
            // 發生例外則發送至iTalking
            $italkingOperator = $this->getContainer()->get('durian.italking_operator');
            $server = gethostname();

            $iTalkingMsg = sprintf(
                '[%s] [%s] %s',
                $server,
                date('Y-m-d H:i:s'),
                $e->getMessage()
            );

            $italkingOperator->pushExceptionToQueue(
                'developer_acc',
                get_class($e),
                $iTalkingMsg
            );

            // 如果例外發生就把資料推回queue
            $redis->rpush('shopweb_queue', json_encode($shopWebInfo));
        }

        $bgMonitor->setMsgNum($count);
        $bgMonitor->commandEnd();
    }

    /**
     * 進行發送購物網通知
     *
     * @param string $shopWebInfo
     */
    private function shopWeb($shopWebInfo)
    {
        $ip = $this->getContainer()->getParameter('shopweb_ip');
        $url = $shopWebInfo['url'];
        $params = $shopWebInfo['params'];
        $parseUrl = parse_url($url);

        $host = sprintf(
            'shop.%s.%s',
            $parseUrl['scheme'],
            $parseUrl['host']
        );

        $fields = [
            'user_name' => $params['username'],
            'gold' => $params['amount'],
            'order_sn' => $params['entry_id']
        ];

        $request = new FormRequest('GET', '/admin/auto_order.php', $ip);
        $request->addFields($fields);
        $request->addHeader("Host: $host");

        $client = new Curl();

        if ($this->client) {
            $client = $this->client;
        }

        // timeout設為30秒
        $client->setOption(CURLOPT_TIMEOUT, 30);

        $response = new Response();

        if ($this->response) {
            $response = $this->response;
        }

        $result = '';

        try {
            $client->send($request, $response);

            if ($response->getStatusCode() != 200) {
                throw new \RuntimeException('Send shop web has not succeeded');
            }
        } catch (\Exception $e) {
            $result = "通知購物網失敗，請檢查 $ip 到 $host 的線路是否正常。";
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

        $logger = $this->getContainer()->get('durian.logger_manager')->setUpLogger('shopweb.log');
        $logger->addInfo($logContent);
        $logger->popHandler()->close();

        $ret = json_decode($result, true);
    }
}
