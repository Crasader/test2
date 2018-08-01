<?php
namespace BB\DurianBundle\Message;

use BB\DurianBundle\Message\BaseWorker;

/**
 * italking 訊息處理
 *
 * @author Sweet 2015.01.30
 */
class ITalkingWorker extends BaseWorker
{
    /**
     * 重複的例外訊息
     *
     * @var array
     */
    private $duplicateException = [];

    /**
     * Construct
     */
    public function __construct()
    {
        $this->interval = 500000;
    }

    /**
     * 回傳 server ip
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

    /**
     * 將 italking 訊息推入 redis queue
     *
     * @param array $params 訊息相關參數
     */
    public function push($params)
    {
        $redis = $this->container->get('snc_redis.default_client');
        $this->checkKeyExist($params);

        $message = [
            'target' => 'italking',
            'error_count' => 0,
            'content' => [
                'user' => $this->getITalkingUser(),
                'password' => $this->getITalkingPassword(),
                'type' => $params['type'],
                'message' => $params['message'],
                'code' => $this->getITalkingGmCode()
            ]
        ];

        if (isset($params['exception'])) {
            $message['content']['exception'] = $params['exception'];
        }

        $domain = '';
        if (isset($params['domain'])) {
            $domain = $params['domain'];
        }

        $allowedType = [
            'payment_alarm',
            'account_fail'
        ];

        // 需送到 esball 的 italking 訊息
        if ($domain == 6 && in_array($params['type'], $allowedType)) {
            $message['content']['code'] = $this->getITalkingEsballCode();
        }

        // 需送到 bet9 的 italking 訊息
        if ($domain == 98 && in_array($params['type'], $allowedType)) {
            $message['content']['code'] = $this->getITalkingBet9Code();
        }

        // 需送到 kresball 的 italking 訊息
        if ($domain == 3820175 && $params['type'] == 'account_fail_kr') {
            $message['content']['code'] = $this->getITalkingKresballCode();
        }

        // 需送到 esball global 的 italking 訊息
        if ($domain == 3819935 && $params['type'] == 'account_fail') {
            $message['content']['code'] = $this->getITalkingEsballGlobalCode();
        }

        // 需送到 eslot 的 italking 訊息
        if ($domain == 3820190 && $params['type'] == 'account_fail') {
            $message['content']['code'] = $this->getITalkingEslotCode();
        }

        $redis->lpush($this->getQueueName(), json_encode($message));
    }

    /**
     * 傳送訊息的前置處理
     *
     * @param array $message 訊息
     * @return boolean
     */
    protected function preSend($message)
    {
        // 重複的例外訊息只送一次
        if (isset($message['content']['exception'])) {
            $match = [];
            $encodeMessage = json_encode($message);

            if (preg_match('/ErrorMessage\: (.*) \[/', $message['content']['message'], $match)) {
                // api 例外訊息
                $content = $match[1] . '/' . $message['error_count'];
                if (isset($this->duplicateException[$content])) {
                    return false;
                }

                $this->duplicateException[$content] = $encodeMessage;
            } else {
                // command 例外訊息
                if (isset($this->duplicateException[$encodeMessage])) {
                    return false;
                }

                $this->duplicateException[$encodeMessage] = $encodeMessage;
            }
        }

        // 無 italking 不送訊息
        if (!$this->getIp($message)) {
            return false;
        }

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
        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Send italking message failed', 150660016);
        }

        $responseContent = json_decode($response->getContent(), true);
        if ($responseContent['code'] !== 0) {
            throw new \RuntimeException('Send italking message failed with error response', 150660017);
        }

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
        return $this->container->getParameter('italking_method');
    }

    /**
     * 回傳 url
     *
     * @param array $message 訊息
     * @return string
     */
    protected function getUrl($message)
    {
        return $this->container->getParameter('italking_url');
    }

    /**
     * 回傳 ip
     *
     * @param array $message 訊息
     * @return string
     */
    protected function getIp($message)
    {
        return $this->container->getParameter('italking_ip');
    }

    /**
     * 回傳 domain
     *
     * @param array $message 訊息
     * @return string
     */
    protected function getDomain($message)
    {
        return $this->container->getParameter('italking_domain');
    }

    /**
     * 確認要傳送的訊息資料是否齊全
     *
     * @param array $params 訊息相關參數
     */
    private function checkKeyExist($params)
    {
        if (!array_key_exists('type', $params)) {
            throw new \InvalidArgumentException('No type specified in italking message', 150660018);
        }

        if (!$params['type']) {
            throw new \InvalidArgumentException('Invalid type in italking message', 150660019);
        }

        if (!array_key_exists('message', $params)) {
            throw new \InvalidArgumentException('No message specified in italking message', 150660020);
        }

        if (!$params['message']) {
            throw new \InvalidArgumentException('Invalid message in italking message', 150660021);
        }
    }

    /**
     * 回傳 italking user
     *
     * @return string
     */
    private function getITalkingUser()
    {
        return $this->container->getParameter('italking_user');
    }

    /**
     * 回傳 italking password
     *
     * @return string
     */
    private function getITalkingPassword()
    {
        return $this->container->getParameter('italking_password');
    }

    /**
     * 回傳 italking gm code
     *
     * @return string
     */
    private function getITalkingGmCode()
    {
        return $this->container->getParameter('italking_gm_code');
    }

    /**
     * 回傳 italking esball code
     *
     * @return string
     */
    private function getITalkingEsballCode()
    {
        return $this->container->getParameter('italking_esball_code');
    }

    /**
     * 回傳 italking bet9 code
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
}
