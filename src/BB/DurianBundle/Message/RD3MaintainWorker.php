<?php
namespace BB\DurianBundle\Message;

use BB\DurianBundle\Message\RD3Worker;

/**
 * 研三維護訊息處理
 */
class RD3MaintainWorker extends RD3Worker
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

        $beginAt->setTimezone(new \DateTimeZone('Etc/GMT+4'));
        $endAt->setTimezone(new \DateTimeZone('Etc/GMT+4'));

        $message = [
            'target' => 'rd3_maintain',
            'method' => 'GET',
            'url' => '/app/WebService/view/display.php/GameRenovate',
            'error_count' => 0,
            'content' => [
                'gamekind' => $code,
                'start_date' => $beginAt->format('Y-m-d'),
                'starttime' => $beginAt->format('H:i:s'),
                'end_date' => $endAt->format('Y-m-d'),
                'endtime' => $endAt->format('H:i:s'),
                'message' => $msg,
                'state' => 'n'
            ]
        ];

        if ($isMaintain == true) {
            $message['content']['state'] = 'y';
        }

        // 歐博的維護API，最慢可能會執行到30秒左右，將timeout設為30秒
        if ($code == 22) {
            $message['timeout'] = 30;
        }

        return $message;
    }

    /**
     * 確認要傳送的訊息資料是否齊全
     *
     * @param array $params 訊息相關參數
     */
    protected function checkKeyExist($params)
    {
        if (!array_key_exists('maintain', $params)) {
            throw new \InvalidArgumentException('No maintain specified in RD3 maintain message', 150660065);
        }

        if (!$params['maintain']) {
            throw new \InvalidArgumentException('Invalid maintain in RD3 maintain message', 150660066);
        }

        if (!array_key_exists('now_time', $params)) {
            throw new \InvalidArgumentException('No now_time specified in RD3 maintain message', 150660067);
        }

        if (!$params['now_time']) {
            throw new \InvalidArgumentException('Invalid now_time in RD3 maintain message', 150660068);
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
            throw new \RuntimeException('Send RD3 maintain message failed', 150660063);
        }

        $responseContent = json_decode($response->getContent(), true);
        if ($responseContent['result'] !== 'ok' && $responseContent['result'] !== 'true') {
            throw new \RuntimeException('Send RD3 maintain message failed with error response', 150660064);
        }

        return true;
    }
}
