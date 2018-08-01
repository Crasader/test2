<?php
namespace BB\DurianBundle\Message;

use BB\DurianBundle\Message\RD1Worker;

class RD1WhitelistWorker extends RD1Worker
{
    /**
     * 產生要推入redis的訊息
     *
     * @param array $params 訊息相關參數
     * @return array
     */
    protected function createPushMessage($params)
    {
        $message = [
            'target_param' => 'rd1_maintain',
            'target' => 'rd1_whitelist',
            'error_count' => 0,
            'method' => 'POST',
            'url' => '/api/index.php?module=MaintainAPI&method=setWhiteList',
            'header' => ['Api-Key' => $this->container->getParameter('rd1_api_key')],
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
            throw new \RuntimeException('Send RD1 whitelist message failed', 150660035);
        }

        $responseContent = json_decode($response->getContent(), true);
        if ($responseContent['result'] !== 'ok') {
            throw new \RuntimeException('Send RD1 whitelist message failed with error response', 150660036);
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
            throw new \InvalidArgumentException('No content specified in RD1 whitelist message', 150660037);
        }

        if (!$params['content']) {
            throw new \InvalidArgumentException('Invalid content in RD1 whitelist message', 150660038);
        }
    }
}
