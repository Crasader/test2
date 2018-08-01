<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;
use Buzz\Message\Request;
use Buzz\Client\Curl;
use Buzz\Message\Response;

/**
 * 伍佰支付
 */
class Pay500 extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantNo' => '', // 商戶號
        'outTradeNo' => '', // 商戶訂單號
        'currency' => 'CNY', // 貨幣類型，固定值
        'amount' => '', // 金額，單位：分
        'content' => '', // 交易主題，設定username方便業主比對
        'payType' => '', // 支付類型
        'callbackURL' => '', // 異步通知網址
    ];

    /**
     * 支付提交請求頭
     *
     * @var array
     */
    protected $requestHeader = [
        'x-oapi-pv' => '0.0.1', // API協議版本，固定值
        'x-oapi-sdkv' => '0.0.1', // SDK版本，固定值
        'x-oapi-sk' => '', // 證書編號
        'x-oapi-sm' => 'MD5', // 簽名方式，固定值
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantNo' => 'number',
        'outTradeNo' => 'orderId',
        'amount' => 'amount',
        'content' => 'username',
        'payType' => 'paymentVendorId',
        'callbackURL' => 'notify_url',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantNo' => 1,
        'tradeNo' => 1,
        'outTradeNo' => 1,
        'outContext' => 0,
        'payType' => 1,
        'currency' => 1,
        'amount' => 1,
        'payedAmount' => 1,
        'status' => 1,
        'settleType' => 1,
        'settlePeriod' => 0,
        'settleFeeRate' => 0,
        'settleFee' => 0,
        'receiverBankName' => 0,
        'receiverBranchBankName' => 0,
        'receiverBranchBankCode' => 0,
        'receiverCardNo' => 0,
        'receiverAccountName' => 0,
        'receiverPhone' => 0,
        'receiverIdNo' => 0,
        'errorCode' => 0,
        'errorMsg' => 0,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'SUCCEED';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1103 => 'QQ_QRCODE_PAY', // QQ_二維
        1104 => 'QQ_WAP_PAY', // QQ_手機支付
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->payVerify();

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['payType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['merchantCerNo']);

        // 額外的參數設定
        $this->requestData['payType'] = $this->bankMap[$this->requestData['payType']];
        $this->requestData['amount'] = round($this->requestData['amount'] * 100);

        // 設定請求頭證書編號
        $this->requestHeader['x-oapi-sk'] = $merchantExtraValues['merchantCerNo'];

        // 設定加密簽名
        $this->requestHeader['x-oapi-sign'] = $this->encode();

        // 設定請求頭編碼格式
        $this->requestHeader['Content-Type'] = 'application/json;charset=utf-8';

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/native/com.opentech.cloud.easypay.trade.create/0.0.1',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->requestData),
            'header' => $this->requestHeader,
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['paymentInfo'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 手機支付
        if ($this->options['paymentVendorId'] == 1104) {
            return ['act_url' => $parseData['paymentInfo']];
        }

        $this->setQrcode($parseData['paymentInfo']);

        return [];
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $this->verifyPrivateKey();

        $encodeData = [];

        $encodeData[] = $this->options['notify_url'];

        if (!isset($this->options['headers']['x-oapi-sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (!isset($this->options['body'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $sign = $this->options['headers']['x-oapi-sign'];
        unset($this->options['headers']['x-oapi-sign']);

        ksort($this->options['headers']);

        $encodeData[] = urldecode(http_build_query($this->options['headers']));
        $encodeData[] = $this->options['body'];
        $encodeData[] = $this->privateKey;

        $encodeStr = implode('&', $encodeData);

        if ($sign != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        $this->options = json_decode($this->options['body'], true);

        $this->payResultVerify();

        if ($this->options['status'] !== 'SETTLED' && $this->options['status'] !== 'PAYED') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['outTradeNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['payedAmount'] != round($entry['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 支付時的加密
     *
     * @return string
     */
    protected function encode()
    {
        $encodeData = [];

        $encodeData[] = $this->options['postUrl'];

        ksort($this->requestHeader);

        $encodeData[] = urldecode(http_build_query($this->requestHeader));
        $encodeData[] = json_encode($this->requestData);
        $encodeData[] = $this->privateKey;

        $encodeStr = implode('&', $encodeData);

        return strtoupper(md5($encodeStr));
    }

    /**
     * 發送curl請求
     *
     * @param array $curlParam 參數說明如下
     *     method  string 提交方式
     *     uri     string
     *     ip      array
     *     host    string
     *     param   array  提交的參數
     *     header  array  需要的header
     *     timeout integer 連線超時時間
     *     charset string 字符集
     *
     * @return string Response Content
     */
    protected function curlRequest($curlParam)
    {
        $logger = $this->container->get('durian.payment_logger');

        foreach ($curlParam['ip'] as $ip) {
            try {
                $request = new Request($curlParam['method']);

                $request->setContent($curlParam['param']);
                $request->fromUrl($ip . $curlParam['uri']);
                $request->setHeaders($curlParam['header']);
                $request->addHeader("Host: {$curlParam['host']}");

                $client = new Curl();

                if ($this->client) {
                    $client = $this->client;
                }

                // 關閉curl ssl憑證檢查
                $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
                $client->setOption(CURLOPT_SSL_VERIFYPEER, false);

                // 如果沒有指定超時時間，預設為10秒
                if (!isset($curlParam['timeout'])) {
                    $curlParam['timeout'] = 10;
                }

                $client->setOption(CURLOPT_TIMEOUT, $curlParam['timeout']);

                $response = new Response();

                try {
                    $client->send($request, $response);
                } catch (\Exception $e) {
                    throw new PaymentConnectionException(
                        'Payment Gateway connection failure',
                        180088,
                        $this->getEntryId()
                    );
                }

                if ($this->response) {
                    $response = $this->response;
                }

                $result = trim($response->getContent());

                // 取得Header內的編碼來當做轉換編碼時的依據
                $header = $response->getHeader('Content-Type');

                // 如果 Header 沒有給定 charset, 則使用 UTF-8 做轉換編碼依據
                if (!empty($header)) {
                    $charset = [];

                    // 要抓的的格式為英數字及-號
                    preg_match('/charset=([\w-]+)/', $header, $charset);

                    if (!isset($charset[1])) {
                        $detach = ['GB2312', 'UTF-8', 'GBK'];
                        $charset[1] = mb_detect_encoding($result, $detach);
                    }

                    $result = iconv($charset[1], 'UTF-8', $result);
                }

                // 紀錄log
                $message = [
                    'serverIp' => $ip,
                    'host' => $curlParam['host'],
                    'method' => $curlParam['method'],
                    'uri' => $curlParam['uri'],
                    'param' => $this->curlRequestDecode($curlParam['param']),
                    'output' => urldecode($this->curlResponseDecode($result)),
                ];

                $logger->record($message);

                if ($response->getStatusCode() != 200) {
                    throw new PaymentConnectionException(
                        'Payment Gateway connection failure',
                        180088,
                        $this->getEntryId()
                    );
                }

                $errorCode = $response->getHeader('x-oapi-error-code');

                if (is_null($errorCode)) {
                    throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
                }

                $msg = urldecode($response->getHeader('x-oapi-msg'));

                if ($errorCode != 'SUCCEED' && $msg != '') {
                    throw new PaymentConnectionException($msg, 180130, $this->getEntryId());
                }

                if ($errorCode != 'SUCCEED') {
                    throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
                }

                if (!$result) {
                    throw new PaymentConnectionException('Empty Payment Gateway response', 180089, $this->getEntryId());
                }

                return $result;
            } catch (\Exception $e) {
                if (end($curlParam['ip']) == $ip) {
                    throw $e;
                }

                continue;
            }
        }
    }
}
