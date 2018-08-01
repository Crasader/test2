<?php
namespace BB\DurianBundle\Logger;

use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * 支付平台對外連線的紀錄
 */
class Payment extends ContainerAware
{
    /**
     * 需要遮罩的參數名
     *
     * @var array
     */
    private $maskParams = [];

    /**
     * 寫支付平台對外連線的log
     *
     * @param $message 需要記錄的參數，共有以下參數：
     *     serverIp 提交ip
     *     host     提交域名
     *     method   提交方式
     *     uri      提交uri
     *     param    提交參數
     *     output   返回結果
     */
    public function record($message)
    {
        //預先設定好logger與儲存的log檔
        $handler = $this->container->get('monolog.handler.payment');

        $logger = $this->container->get('logger');
        $logger->pushHandler($handler);

        $maskRequest = $this->maskRequest($message['param']);
        $maskedResponse = $this->maskResponse($message['output']);
        $maskedUri = $this->maskUriKey($message['uri']);

        $logContent = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $message['serverIp'],
            $message['host'],
            $message['method'],
            $maskedUri,
            $maskRequest,
            $maskedResponse
        );

        $logger->info($logContent);
        $logger->popHandler()->close();
    }

    /**
     * 寫金流相關操作紀錄
     *
     * @param array $output
     */
    public function writeLog($output)
    {
        $request = $this->container->get('request');

        $server = gethostname();
        $clientIp = $request->getClientIp();
        $method = $request->getMethod();
        $uri = $request->getPathInfo();
        $param = $request->request->all();

        // 密鑰遮罩
        if (isset($param['private_key'])) {
            $param['private_key'] = '******';
        }

        $paramString = urldecode(http_build_query($param));
        $outputString = urldecode(http_build_query($output));

        $logContent = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $server,
            $clientIp,
            $method,
            $uri,
            $paramString,
            $outputString
        );

        $logger = $this->container->get('durian.logger_manager')->setUpLogger('payment_operation.log');
        $logger->addInfo($logContent);
        $logger->popHandler()->close();
    }

    /**
     * 寫出入款OP紀錄
     *
     * @param array $param
     * @param string $output
     */
    public function writeOpLog($param, $output)
    {
        $request = $this->container->get('request');

        $server = gethostname();
        $clientIp = $request->getClientIp();

        $paramString = urldecode(http_build_query($param));
        $outputString = $output;

        $logContent = sprintf(
            '%s %s "REQUEST: %s" "RESPONSE: %s"',
            $server,
            $clientIp,
            $paramString,
            $outputString
        );

        $logger = $this->container->get('durian.logger_manager')->setUpLogger('payment_op.log');
        $logger->addInfo($logContent);
        $logger->popHandler()->close();
    }

    /**
     * 提交增加遮罩
     *
     * @param string $paramStr
     * @return string
     */
    private function maskRequest($paramStr)
    {
        $params = [];
        $this->maskParams = [
            'password',
            'second_password',
            'api_code',
            'secretid',
            'key',
        ];

        if ($this->isJson($paramStr)) {
            $params = $this->maskPassword(json_decode($paramStr, true));

            return json_encode($params);
        }

        parse_str($paramStr, $params);
        $params = $this->maskPassword($params);

        return urldecode(http_build_query($params));
    }

    /**
     * 返回增加遮罩
     *
     * @param string $outputStr
     * @return string
     */
    private function maskResponse($outputStr)
    {
        if (!$this->isJson($outputStr)) {
            return $outputStr;
        }

        $this->maskParams = [
            'xpriv',
            'xpub',
            'cache',
            'extendedPublicKey',
            'extendedPrivateKey',
            'from',
        ];

        $outputs = json_decode($outputStr, true);
        $outputs = $this->maskPassword($outputs);

        return json_encode($outputs);
    }

    /**
     * Uri公鑰增加遮罩
     *
     * @param string $uri
     * @return string
     */
    private function maskUriKey($uri)
    {
        $replaceUri = preg_replace('@/accounts/xpub[\w]*@', '/accounts/******', $uri);

        return $replaceUri;
    }

    /**
     * 檢查是否為json格式
     *
     * @param string $content
     * @return boolean
     */
    private function isJson($content)
    {
        return is_string($content) && is_array(json_decode($content, true)) && (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * 密碼增加遮罩
     *
     * @param array $params
     * @return array
     */
    private function maskPassword($params)
    {
        foreach ($this->maskParams as $maskParam) {
            if (isset($params[$maskParam])) {
                $params[$maskParam] = '******';
            }
        }

        return $params;
    }
}
