<?php
namespace BB\DurianBundle\Message;

use BB\DurianBundle\Message\RD1Worker;

/**
 * 研一維護訊息處理
 */
class RD1MaintainWorker extends RD1Worker
{
    /**
     * 產生要推入redis的訊息
     *
     * @param array $params 訊息相關參數
     * @return array
     */
    protected function createPushMessage($params)
    {
        $code = $params['maintain']->getCode();
        $beginAt = $params['maintain']->getBeginAt();
        $endAt = $params['maintain']->getEndAt();
        $msg = $params['maintain']->getMsg();
        $isMaintain = $this->checkIsMaintain($params);

        $message = [
            'target' => 'rd1_maintain',
            'error_count' => 0,
            'method' => 'GET',
            'url' => '/api/index.php?module=MaintainAPI&method=SetMaintain',
            'content' => [
                'code' => $code,
                'begin_at' => $beginAt->format(\DateTime::ISO8601),
                'end_at' => $endAt->format(\DateTime::ISO8601),
                'msg' => $msg,
                'is_maintaining' => 'false'
            ]
        ];

        if ($isMaintain == true) {
            $message['content']['is_maintaining'] = 'true';
        }

        return $message;
    }

    /**
     * 回傳 ip
     *
     * @param array $message 訊息
     * @return string
     */
    protected function getIp($message)
    {
        return $this->container->getParameter('rd1_maintain_ip');
    }

    /**
     * 回傳 domain
     *
     * @param array $message 訊息
     * @return string
     */
    protected function getDomain($message)
    {
        return $this->container->getParameter('rd1_maintain_domain');
    }

    /**
     * 確認要傳送的訊息資料是否齊全
     *
     * @param array $params 訊息相關參數
     */
    protected function checkKeyExist($params)
    {
        if (!array_key_exists('maintain', $params)) {
            throw new \InvalidArgumentException('No maintain specified in RD1 maintain message', 150660031);
        }

        if (!$params['maintain']) {
            throw new \InvalidArgumentException('Invalid maintain in RD1 maintain message', 150660032);
        }

        if (!array_key_exists('now_time', $params)) {
            throw new \InvalidArgumentException('No now_time specified in RD1 maintain message', 150660033);
        }

        if (!$params['now_time']) {
            throw new \InvalidArgumentException('Invalid now_time in RD1 maintain message', 150660034);
        }
    }

    /**
     * 確認是否為維護期間
     *
     * @param array $params 訊息相關參數
     * @return boolean
     */
    private function checkIsMaintain($params)
    {
        $beginAt = $params['maintain']->getBeginAt();
        $endAt = $params['maintain']->getEndAt();
        $nowTime = $params['now_time'];
        $atMaintainTime = $beginAt <= $nowTime && $nowTime < $endAt;

        if ($atMaintainTime && $beginAt != $endAt) {
            return true;
        }

        return false;
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
            throw new \RuntimeException('Send RD1 maintain message failed', 150660029);
        }

        $responseContent = json_decode($response->getContent(), true);
        if ($responseContent['result'] !== 'ok' && $responseContent['result'] !== 'true') {
            throw new \RuntimeException('Send RD1 maintain message failed with error response', 150660030);
        }

        return true;
    }
}
