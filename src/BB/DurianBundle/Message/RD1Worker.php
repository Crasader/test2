<?php
namespace BB\DurianBundle\Message;

use BB\DurianBundle\Message\BaseWorker;

/**
 * 研一訊息處理
 */
class RD1Worker extends BaseWorker
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
            'target' => 'rd1',
            'error_count' => 0,
            'method' => $params['method'],
            'url' => $params['url'],
            'content' => $params['content']
        ];

        // 研一有2組domain/ip參數，如不是rd1，需多帶target_param指定
        if (isset($params['target_param'])) {
            $message['target_param'] = $params['target_param'];
        }

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
            throw new \RuntimeException('Send RD1 message failed', 150660022);
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
        if (isset($message['target_param'])) {
            return $this->container->getParameter($message['target_param'] . '_ip');
        }

        return $this->container->getParameter('rd1_ip');
    }

    /**
     * 回傳 domain
     *
     * @param array $message 訊息
     * @return string
     */
    protected function getDomain($message)
    {
        if (isset($message['target_param'])) {
            return $this->container->getParameter($message['target_param'] . '_domain');
        }

        return $this->container->getParameter('rd1_domain');
    }

    /**
     * 確認要傳送的訊息資料是否齊全
     *
     * @param array $params 訊息相關參數
     */
    protected function checkKeyExist($params)
    {
        if (!array_key_exists('method', $params)) {
            throw new \InvalidArgumentException('No method specified in RD1 message', 150660023);
        }

        if (!$params['method']) {
            throw new \InvalidArgumentException('Invalid method in RD1 message', 150660024);
        }

        if (!array_key_exists('url', $params)) {
            throw new \InvalidArgumentException('No url specified in RD1 message', 150660025);
        }

        if (!$params['url']) {
            throw new \InvalidArgumentException('Invalid url in RD1 message', 150660026);
        }

        if (!array_key_exists('content', $params)) {
            throw new \InvalidArgumentException('No content specified in RD1 message', 150660027);
        }

        if (!$params['content']) {
            throw new \InvalidArgumentException('Invalid content in RD1 message', 150660028);
        }
    }
}
