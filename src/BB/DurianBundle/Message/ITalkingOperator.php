<?php
namespace BB\DurianBundle\Message;

use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response;
use Buzz\Client\Curl;
use Buzz\Listener\LoggerListener;

class ITalkingOperator
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     *
     * @var \Buzz\Message\Response
     */
    private $response;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * @var \Predis\Client
     */
    private $redis;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

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
     * 送訊息至iTalking
     *
     * @param array $msgArray
     * @return boolean
     */
    public function sendMessageToITalking($msgArray)
    {
        $logger = $this->container->get('durian.logger_manager')
            ->setUpLogger('to_italking_http_detail.log');

        $parameters = [
            'user'     => $this->getITalkingUser(),
            'password' => $this->getITalkingPassword()
        ];

        $parameters = array_merge($parameters, $msgArray);

        //連到italking送訊息
        $client = new Curl();

        if ($this->client) {
            $client = $this->client;
        }

        $request = new FormRequest('POST', $this->getITalkingUrl(), $this->getITalkingIp());
        $request->addFields($parameters);
        $request->addHeader("Host: {$this->getITalkingDomain()}");

        $response = new Response();

        $listener = new LoggerListener(array($logger, 'addDebug'));
        $listener->preSend($request);

        $client->send($request, $response);

        $listener->postSend($request, $response);

        if ($this->response) {
            $response = $this->response;
        }

        $logger->addDebug($request . $response);

        $logger->popHandler()->close();

        $returnMsg = json_decode($response->getContent(), true);

        if ($response->getStatusCode() == 200 && $returnMsg['code'] === 0) {
            return true;
        } else {
            throw new \RuntimeException("Fail to send message");
        }
    }

    /**
     * 測試iTalking連線狀態是否正常
     * @return boolean
     */
    public function checkITalkingStatus()
    {
        //連到italking送訊息
        $client = new Curl();

        if ($this->client) {
            $client = $this->client;
        }

        $request = new FormRequest('POST', $this->getITalkingUrl(), $this->getITalkingIp());
        $request->addHeader("Host: {$this->getITalkingDomain()}");

        $response = new Response();

        $client->send($request, $response);

        if ($this->response) {
            $response = $this->response;
        }

        if ($response->getStatusCode() == 200) {
            return true;
        } else {
            throw new \RuntimeException("Fail to send message");
        }
    }

    /**
     * 把要送至iTalking的例外訊息推至queue中
     *
     * @param string $type 傳送類別
     * @param string $exception 例外型態
     * @param string $message 例外訊息
     */
    public function pushExceptionToQueue($type, $exception, $message)
    {
        if (!$this->redis) {
            $this->redis = $this->container->get('snc_redis.default_client');
        }

        $msg = [
            'type'      => $type,
            'exception' => $exception,
            'message'   => $message,
            'code'      => $this->getITalkingGMCode()
        ];

        $this->redis->lpush('italking_exception_queue', json_encode($msg));
    }

    /**
     * 把要送至iTalking的訊息推至queue中
     *
     * @param string $type
     * @param string $message
     * @param integer $domain
     */
    public function pushMessageToQueue($type, $message, $domain = null)
    {
        if (!$this->redis) {
            $this->redis = $this->container->get('snc_redis.default_client');
        }

        $msg = [
            'type'    => $type,
            'message' => $message,
            'code'    => $this->getITalkingGMCode()
        ];

        $typeArray = [
            'payment_alarm',
            'account_fail'
        ];

        //domain = 6，要送到esball的italking
        if (in_array($type, $typeArray) && $domain == 6) {
            $msg['code'] = $this->getITalkingEsballCode();
        }

        //domain = 98，要送到博九的italking
        if (in_array($type, $typeArray) && $domain == 98) {
            $msg['code'] = $this->getITalkingBet9Code();
        }

        // domain = 3820175，要送到 kresball 的 italking
        if ($type == 'account_fail_kr' && $domain == 3820175) {
            $msg['code'] = $this->getITalkingKresballCode();
        }

        // domain = 3819935，要送到 esball global 的 italking
        if ($type == 'account_fail' && $domain == 3819935) {
            $msg['code'] = $this->getITalkingEsballGlobalCode();
        }

        // domain = 3820190，要送到 eslot 的 italking
        if ($type == 'account_fail' && $domain == 3820190) {
            $msg['code'] = $this->getITalkingEslotCode();
        }

        $this->redis->lpush($this->getQueueName(), json_encode($msg));
    }

    /**
     * 取得 Queue 名稱
     *
     * @return String
     */
    public function getQueueName()
    {
        return 'italking_message_queue';
    }

    /**
     * 回傳iTalking domain
     *
     * @return string
     */
    private function getITalkingDomain()
    {
        return $this->container->getParameter('italking_domain');
    }

    /**
     * 回傳iTalking ip
     *
     * @return string
     */
    public function getITalkingIp()
    {
        return $this->container->getParameter('italking_ip');
    }

    /**
     * 回傳iTalking url
     *
     * @return string
     */
    public function getITalkingUrl()
    {
        return $this->container->getParameter('italking_url');
    }

    /**
     * 回傳iTalking user
     *
     * @return string
     */
    private function getITalkingUser()
    {
        return $this->container->getParameter('italking_user');
    }

    /**
     * 回傳iTalking password
     *
     * @return string
     */
    private function getITalkingPassword()
    {
        return $this->container->getParameter('italking_password');
    }

    /**
     * 回傳iTalking gm code
     *
     * @return string
     */
    private function getITalkingGMCode()
    {
        return $this->container->getParameter('italking_gm_code');
    }

    /**
     * 回傳iTalking esball code
     *
     * @return string
     */
    private function getITalkingEsballCode()
    {
        return $this->container->getParameter('italking_esball_code');
    }

    /**
     * 回傳iTalking 博九 code
     *
     * @return string
     */
    private function getITalkingBet9Code()
    {
        return $this->container->getParameter('italking_bet9_code');
    }

    /**
     * 回傳 iTalking kresball code
     *
     * @return string
     */
    private function getITalkingKresballCode()
    {
        return $this->container->getParameter('italking_kresball_code');
    }

    /**
     * 回傳 iTalking esball global code
     *
     * @return string
     */
    private function getITalkingEsballGlobalCode()
    {
        return $this->container->getParameter('italking_esball_global_code');
    }

    /**
     * 回傳 iTalking eslot code
     *
     * @return string
     */
    private function getITalkingEslotCode()
    {
        return $this->container->getParameter('italking_eslot_code');
    }

    /**
     * 回傳server ip
     *
     * @return string
     */
    public function getServerIp()
    {
        $ipList = gethostbynamel(gethostname());

        if (!$ipList) {
            return null;
        }

        foreach ($ipList as $ip) {
            if ($ip !== '127.0.0.1') {
                return $ip;
            }
        }

        return null;
    }
}
