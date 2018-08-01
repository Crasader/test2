<?php
namespace BB\DurianBundle\Message;

use BB\DurianBundle\Message\MobileWorker;

class MobileWhitelistWorker extends MobileWorker
{
    /**
     * 產生要推入redis的訊息
     *
     * @param array $params 訊息相關參數
     * @return array
     */
    protected function createPushMessage($params)
    {
        $url = $this->container->getParameter('whitelist_mobile_url');
        $key = $this->container->getParameter('whitelist_mobile_key');

        $message = [
            'target' => 'mobile_whitelist',
            'error_count' => 0,
            'method' => 'POST',
            'url' => $url,
            'header' => ['Ekey' => $key],
            'content' => $params['content']
        ];

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
            throw new \RuntimeException('Send Mobile whitelist message failed', 150660069);
        }

        $responseContent = json_decode($response->getContent(), true);

        if (!isset($responseContent['status']) || $responseContent['status'] !== '000') {
            throw new \RuntimeException('Send Mobile whitelist message failed with error response', 150660070);
        }

        return true;
    }

    /**
     * 確認要傳送的訊息資料是否齊全
     *
     * @param array $params 訊息相關參數
     */
    protected function checkKeyExist($params)
    {
        if (!array_key_exists('content', $params)) {
            throw new \InvalidArgumentException('No content specified in Mobile whitelist message', 150660071);
        }

        if (!$params['content']) {
            throw new \InvalidArgumentException('Invalid content in Mobile whitelist message', 150660072);
        }
    }
}
