<?php
namespace BB\DurianBundle\Message;

use Symfony\Component\DependencyInjection\ContainerAware;
use Buzz\Client\Curl;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Request;
use Buzz\Message\Response;
use Buzz\Listener\LoggerListener;

/**
 * 訊息處理
 *
 * @author Sweet 2015.01.30
 */
class BaseWorker extends ContainerAware
{
    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * @var \Buzz\Message\Response
     */
    private $response;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * 傳送訊息的間隔時間 (單位: microsecond)
     *
     * @var integer
     */
    protected $interval = 0;

    /**
     * 允許傳送失敗的次數, 超過允許次數則進 failed queue, 設為 -1 表示允許無限次傳送失敗
     *
     * @var integer
     */
    protected $allowedTimes = 10;

    /**
     * 連線時限 (單位: second)
     *
     * @var integer
     */
    protected $timeout = null;

    /**
     * queue 名稱
     *
     * @var array
     */
    private $queueName = [
        'message' => 'message_queue',
        'immediateMessage' => 'message_immediate_queue',
        'messageRetry' => 'message_queue_retry',
        'immediateMessageRetry' => 'message_immediate_queue_retry',
        'messageFailed' => 'message_queue_failed',
        'immediateMessageFailed' => 'message_immediate_queue_failed'
    ];

    /**
     * @param \Buzz\Client\Curl $client
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
     * 回傳 queue 名稱
     *
     * @param boolean $immediate 是否為即時訊息
     * @return string
     */
    public function getQueueName($immediate = false)
    {
        if ($immediate) {
            return $this->queueName['immediateMessage'];
        }

        return $this->queueName['message'];
    }

    /**
     * 回傳 retry queue 名稱
     *
     * @param boolean $immediate 是否為即時訊息
     * @return string
     */
    public function getRetryQueueName($immediate = false)
    {
        if ($immediate) {
            return $this->queueName['immediateMessageRetry'];
        }

        return $this->queueName['messageRetry'];
    }

    /**
     * 回傳 failed queue 名稱
     *
     * @param boolean $immediate 是否為即時訊息
     * @return string
     */
    public function getFailedQueueName($immediate = false)
    {
        if ($immediate) {
            return $this->queueName['immediateMessageFailed'];
        }

        return $this->queueName['messageFailed'];
    }

    /**
     * 回傳傳送訊息的間隔時間
     *
     * @param array $message 訊息
     * @return integer
     */
     public function getInterval($message)
     {
         if (isset($message['interval'])) {
            return $message['interval'];
         }

         return $this->interval;
     }

     /**
     * 回傳允許傳送失敗的次數
     *
     * @param array $message 訊息
     * @return integer
     */
     public function getAllowedTimes($message)
     {
         if (isset($message['allowed_times'])) {
            return $message['allowed_times'];
         }

         return $this->allowedTimes;
     }

    /**
     * 傳送訊息
     *
     * @param array $message 訊息
     * @return boolean
     */
    public function send($message)
    {
        if (!$this->preSend($message)) {
            return false;
        }

        $this->setLogger();

        $client = new Curl();
        if ($this->client) {
            $client = $this->client;
        }

        $method = $this->getMethod($message);
        $url = $this->getUrl($message);
        $ip = $this->getIp($message);
        $domain = $this->getDomain($message);

        $timeout = $this->timeout;
        if (isset($message['timeout'])) {
            $timeout = $message['timeout'];
        }

        $request = new FormRequest($method, $url, $ip);
        $request->addFields($message['content']);

        if (isset($message['target']) && $message['target'] === 'mobile_whitelist') {
            $request = new Request($method, $url, $ip);
            $request->setContent(json_encode($message['content']));
        }

        $request->addHeader("Host: $domain");

        if (isset($message['header'])) {
            foreach ($message['header'] as $headerKey => $headerValue) {
                $request->addHeader("$headerKey: $headerValue");
            }
        }

        $listener = new LoggerListener([$this->logger, 'addDebug']);
        $listener->preSend($request);

        $response = new Response();
        if ($this->container->getParameter('kernel.environment') != 'test') {
            if ($timeout) {
                $client->setTimeout($timeout);
            }
            $client->send($request, $response);
        }

        $listener->postSend($request, $response);

        if ($this->response) {
            $response = $this->response;
        }

        $this->logger->addDebug($request . $response);

        return $this->postSend($response);
    }

    /**
     * 傳送訊息的前置處理
     *
     * @param array $message 訊息
     * @return boolean
     */
    protected function preSend($message)
    {
        return true;
    }

    /**
     * 傳送訊息的後置處理
     *
     * @param Response $response 回傳結果
     * @return boolean
     */
    protected function postSend($response)
    {
        return true;
    }

    /**
     * 回傳 method
     *
     * @param array $message 訊息
     * @return string
     */
    protected function getMethod($message)
    {
        if (!isset($message['method'])) {
            throw new \InvalidArgumentException('No method specified', 150660001);
        }

        return $message['method'];
    }

    /**
     * 回傳 url
     *
     * @param array $message 訊息
     * @return string
     */
    protected function getUrl($message)
    {
        if (!isset($message['url'])) {
            throw new \InvalidArgumentException('No url specified', 150660002);
        }

        return $message['url'];
    }

    /**
     * 回傳 ip
     *
     * @param array $message 訊息
     * @return string
     */
    protected function getIp($message)
    {
        if (!isset($message['ip'])) {
            throw new \InvalidArgumentException('No ip specified', 150660003);
        }

        return $message['ip'];
    }

    /**
     * 回傳 domain
     *
     * @param array $message 訊息
     * @return string
     */
    protected function getDomain($message)
    {
        if (!isset($message['domain'])) {
            throw new \InvalidArgumentException('No domain specified', 150660004);
        }

        return $message['domain'];
    }

    /**
     * 設定 logger
     */
    private function setLogger()
    {
        $logger = $this->container->get('monolog.logger.msg');
        $handler = $this->container->get('monolog.handler.send_message_http_detail');
        $logger->pushHandler($handler);
        $this->logger = $logger;
    }
}
