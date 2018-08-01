<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;
use Buzz\Message\Request;
use Buzz\Client\Curl;
use Buzz\Message\Response;

/**
 * TC-Pay
 */
class TCPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchant_order_no' => '', // 訂單號
        'merchant_order_date' => '', // 訂單時間戳
        'product_name' => '', // 商品名稱
        'remark' => '', // 備註，不可為空
        'amount' => '', // 金額，單位：元，保留小數點兩位
        'notify_url' => '', // 異步通知網址
        'sign_code' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchant_order_no' => 'orderId',
        'merchant_order_date' => 'orderCreateDate',
        'product_name' => 'orderId',
        'remark' => 'orderId',
        'amount' => 'amount',
        'notify_url' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchant_order_no',
        'merchant_order_date',
        'product_name',
        'remark',
        'amount',
        'notify_url',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchant_id' => 1,
        'channel_id' => 1,
        'merchant_order_no' => 1,
        'merchant_order_date' => 1,
        'order_no' => 1,
        'product_name' => 1,
        'remark' => 1,
        'amount' => 1,
        'redirect_url' => 1,
        'status' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1102 => '', // 網銀_收銀台
    ];

    /**
     * 支付平台支援的銀行對應Gateway編號
     *
     * @var array
     */
    private $gatewayMap = [
        1102 => '1', // 網銀_收銀台
    ];

    /**
     * 支付平台支援的銀行對應Method編號
     *
     * @var array
     */
    private $methodMap = [
        1102 => '1', // 網銀_收銀台
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

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查提交需要用到的參數是否存在
        if (!isset($this->options['paymentVendorId']) || !isset($this->options['number'])) {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);

        $date = new \DateTime($this->requestData['merchant_order_date']);
        $this->requestData['merchant_order_date'] = $date->getTimestamp();

        // 設定支付平台需要的加密串
        $this->requestData['sign_code'] = $this->encode();

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['accessToken']);
        $accessToken = $merchantExtraValues['accessToken'];

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $gatewayId = $this->gatewayMap[$this->options['paymentVendorId']];
        $methodId = $this->methodMap[$this->options['paymentVendorId']];

        $curlParam = [
            'method' => 'POST',
            'uri' => "/api/merchants/{$this->options['number']}/gateways/{$gatewayId}/methods/{$methodId}/deposits",
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->requestData, JSON_UNESCAPED_UNICODE),
            'header' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => "Bearer $accessToken",
            ],
        ];
        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['order']['status']) || $parseData['order']['status'] != 1) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if (!isset($parseData['order']['redirect_url'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $urlData = $this->parseUrl($parseData['order']['redirect_url']);

        // Form使用GET才能正常跳轉
        $this->payMethod = 'GET';

        return [
            'post_url' => $urlData['url'],
            'params' => $urlData['params'],
        ];
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        // 先驗證平台回傳的必要參數
        if (!isset($this->options['content'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }
        $content = json_decode($this->options['content'], true);

        // 檢查訂單資訊是否存在
        if (!isset($content['data'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        foreach ($this->decodeParams as $paymentKey => $require) {
            if ($require && !isset($content['data'][$paymentKey])) {
                throw new PaymentException('No return parameter specified', 180137);
            }
        }

        // 直接以支付平台返回作為加密串
        $encodeData = ['data' => json_encode($content['data'], JSON_UNESCAPED_SLASHES)];

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr = strtolower(urlencode($encodeStr));
        $hmac = hash_hmac('sha1', $encodeStr, $this->privateKey, true);

        // 若沒有返回簽名需丟例外
        if (!isset($content['sign_code'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($content['sign_code'] != base64_encode($hmac)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($content['data']['status'] != '2') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($content['data']['merchant_order_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($content['data']['amount'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['accessToken']);
        $accessToken = $merchantExtraValues['accessToken'];

        // 驗證成功需更新代收狀態
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => "/api/merchants/{$entry['merchant_number']}/deposits/{$entry['id']}/confirm",
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => '',
            'header' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => "Bearer $accessToken",
            ],
        ];
        $this->curlRequestWithSuccessfulResponse($curlParam);
    }

    /**
     * 支付時的加密
     *
     * @return string
     */
    protected function encode()
    {
        $encodeData = [];

        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr = strtolower(urlencode($encodeStr));
        $hmac = hash_hmac('sha1', $encodeStr, $this->privateKey, true);

        return base64_encode($hmac);
    }

    /**
     * 發送curl請求並檢查請求是否成功
     *
     * @param array $curlParam 參數說明如下
     *     string method 提交方式
     *     string uri uri
     *     array ip ip陣列
     *     string host host
     *     array param 提交的參數
     *     array header 需要的header
     *     integer timeout 連線超時時間
     *     string charset 字符集
     */
    private function curlRequestWithSuccessfulResponse($curlParam)
    {
        $logger = $this->container->get('durian.payment_logger');

        if ($curlParam['method'] === 'GET') {
            $curlParam['uri'] .= '?' . $curlParam['param'];
        }

        foreach ($curlParam['ip'] as $ip) {
            try {
                $request = new Request($curlParam['method']);

                if ($curlParam['method'] === 'POST') {
                    $request->setContent($curlParam['param']);
                }

                $request->fromUrl($ip . $curlParam['uri']);
                $request->setHeaders($curlParam['header']);
                $request->addHeader("Host: {$curlParam['host']}");

                $client = new Curl();

                if ($this->client) {
                    $client = $this->client;
                }

                //關閉curl ssl憑證檢查
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

                // 先判斷返回是否為XML格式(因轉碼後會無法成功判斷)
                $isXml = $this->isXml($result);

                //取得Header內的編碼來當做轉換編碼時的依據
                $header = $response->getHeader('Content-Type');

                // 若指定字符集，則使用該字符集轉換編碼
                if (isset($curlParam['charset'])) {
                    $header = 'charset=' . $curlParam['charset'];
                }

                //如果 Header 沒有給定 charset, 則使用 UTF-8 做轉換編碼依據
                if (!empty($header)) {
                    $charset = [];

                    //要抓的的格式為英數字及-號
                    preg_match('/charset=([\w-]+)/', $header, $charset);

                    if (!isset($charset[1])) {
                        $detach = ['GB2312', 'UTF-8', 'GBK'];
                        $charset[1] = mb_detect_encoding($result, $detach);
                    }

                    $result = iconv($charset[1], 'UTF-8', $result);
                }

                //紀錄log
                $message = [
                    'serverIp' => $ip,
                    'host' => $curlParam['host'],
                    'method' => $curlParam['method'],
                    'uri' => $curlParam['uri'],
                    'param' => $this->curlRequestDecode($curlParam['param']),
                    'output' => urldecode($this->curlResponseDecode($result))
                ];

                $logger->record($message);

                // 檢查 http status 是否為 2xx
                if (!$response->isSuccessful()) {
                    throw new PaymentConnectionException(
                        'Payment Gateway connection failure',
                        180088,
                        $this->getEntryId()
                    );
                }
            } catch (\Exception $e) {
                if (end($curlParam['ip']) == $ip) {
                    throw $e;
                }

                continue;
            }
        }
    }
}
