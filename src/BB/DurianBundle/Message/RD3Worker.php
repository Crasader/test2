<?php
namespace BB\DurianBundle\Message;

use BB\DurianBundle\Message\BaseWorker;

/**
 * 研三訊息處理
 */
class RD3Worker extends BaseWorker
{
    /**
     * 將訊息推入 redis queue
     *
     * @param array $params 訊息相關參數
     */
    public function push($params)
    {
        $redis = $this->container->get('snc_redis.default_client');
        $this->checkKeyExist($params);

        $message = $this->createPushMessage($params);

        $redis->lpush($this->getQueueName(true), json_encode($message));
    }

    /**
     * 產生要推入redis的訊息
     *
     * @param array $params 訊息相關參數
     * @return array
     */
    protected function createPushMessage($params)
    {
        $message = [
            'target' => 'rd3',
            'error_count' => 0,
            'method' => $params['method'],
            'url' => $params['url'],
            'content' => $params['content']
        ];

        if (isset($params['header'])) {
            $message['header'] = $params['header'];
        }

        return $message;
    }

    /**
     * 傳送訊息的後置處理
     *
     * @param Response $response 回傳結果
     * @return boolean
     */
    protected function postSend($response)
    {
        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Send RD3 message failed', 150660056);
        }

        return true;
    }

    /**
     * 回傳 ip
     *
     * @param array $message 訊息
     * @return string
     */
    protected function getIp($message)
    {
        return $this->container->getParameter('rd3_ip');
    }

    /**
     * 回傳 domain
     *
     * @param array $message 訊息
     * @return string
     */
    protected function getDomain($message)
    {
        return $this->container->getParameter('rd3_domain');
    }

    /**
     * 確認要傳送的訊息資料是否齊全
     *
     * @param array $params 訊息相關參數
     */
    protected function checkKeyExist($params)
    {
        if (!array_key_exists('method', $params)) {
            throw new \InvalidArgumentException('No method specified in RD3 message', 150660057);
        }

        if (!$params['method']) {
            throw new \InvalidArgumentException('Invalid method in RD3 message', 150660058);
        }

        if (!array_key_exists('url', $params)) {
            throw new \InvalidArgumentException('No url specified in RD3 message', 150660059);
        }

        if (!$params['url']) {
            throw new \InvalidArgumentException('Invalid url in RD3 message', 150660060);
        }

        if (!array_key_exists('content', $params)) {
            throw new \InvalidArgumentException('No content specified in RD3 message', 150660061);
        }

        if (!$params['content']) {
            throw new \InvalidArgumentException('Invalid content in RD3 message', 150660062);
        }
    }
}
