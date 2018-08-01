<?php
namespace BB\DurianBundle\Message;

use BB\DurianBundle\Message\BaseWorker;

/**
 * 訊息處理
 *
 * @author Sweet 2015.05.13
 */
class HttpCurlWorker extends BaseWorker
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

        $message = [
            'error_count' => 0,
            'method' => $params['method'],
            'url' => $params['url'],
            'ip' => $params['ip'],
            'domain' => $params['domain'],
            'content' => $params['content']
        ];

        if (isset($params['interval'])) {
            $message['interval'] = $params['interval'];
        }

        if (isset($params['allowed_times'])) {
            $message['allowed_times'] = $params['allowed_times'];
        }

        if (isset($params['timeout'])) {
            $message['timeout'] = $params['timeout'];
        }

        if (isset($params['header'])) {
            $message['header'] = $params['header'];
        }

        $isImmediate = isset($params['immediate']);
        $queueName = $this->getQueueName($isImmediate);

        $redis->lpush($queueName, json_encode($message));
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
            throw new \RuntimeException('Send message failed', 150660005);
        }

        return true;
    }

    /**
     * 確認要傳送的訊息資料是否齊全
     *
     * @param array $params 訊息相關參數
     */
    private function checkKeyExist($params)
    {
        if (!array_key_exists('method', $params)) {
            throw new \InvalidArgumentException('No method specified', 150660006);
        }

        if (!$params['method']) {
            throw new \InvalidArgumentException('Invalid method', 150660007);
        }

        if (!array_key_exists('url', $params)) {
            throw new \InvalidArgumentException('No url specified', 150660008);
        }

        if (!$params['url']) {
            throw new \InvalidArgumentException('Invalid url', 150660009);
        }

        if (!array_key_exists('ip', $params)) {
            throw new \InvalidArgumentException('No ip specified', 150660010);
        }

        if (!$params['ip']) {
            throw new \InvalidArgumentException('Invalid ip', 150660011);
        }

        if (!array_key_exists('domain', $params)) {
            throw new \InvalidArgumentException('No domain specified', 150660012);
        }

        if (!$params['domain']) {
            throw new \InvalidArgumentException('Invalid domain', 150660013);
        }

        if (!array_key_exists('content', $params)) {
            throw new \InvalidArgumentException('No content specified', 150660014);
        }

        if (!$params['content']) {
            throw new \InvalidArgumentException('Invalid content', 150660015);
        }
    }
}
