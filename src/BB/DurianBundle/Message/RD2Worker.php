<?php
namespace BB\DurianBundle\Message;

use BB\DurianBundle\Message\BaseWorker;

/**
 * 研二訊息處理
 */
class RD2Worker extends BaseWorker
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
            'target' => 'rd2',
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
            throw new \RuntimeException('Send RD2 message failed', 150660039);
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
        return $this->container->getParameter('rd2_ip');
    }

    /**
     * 回傳 domain
     *
     * @param array $message 訊息
     * @return string
     */
    protected function getDomain($message)
    {
        return $this->container->getParameter('rd2_domain');
    }

    /**
     * 確認要傳送的訊息資料是否齊全
     *
     * @param array $params 訊息相關參數
     */
    protected function checkKeyExist($params)
    {
        if (!array_key_exists('method', $params)) {
            throw new \InvalidArgumentException('No method specified in RD2 message', 150660040);
        }

        if (!$params['method']) {
            throw new \InvalidArgumentException('Invalid method in RD2 message', 150660041);
        }

        if (!array_key_exists('url', $params)) {
            throw new \InvalidArgumentException('No url specified in RD2 message', 150660042);
        }

        if (!$params['url']) {
            throw new \InvalidArgumentException('Invalid url in RD2 message', 150660043);
        }

        if (!array_key_exists('content', $params)) {
            throw new \InvalidArgumentException('No content specified in RD2 message', 150660044);
        }

        if (!$params['content']) {
            throw new \InvalidArgumentException('Invalid content in RD2 message', 150660045);
        }
    }
}
