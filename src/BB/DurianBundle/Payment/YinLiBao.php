<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;
use Buzz\Message\Request;
use Buzz\Client\Curl;
use Buzz\Message\Response;

/**
 * 銀利寶
 */
class YinLiBao extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'mch_id' => '', // 商戶號
        'trade_type' => '', // 交易類型
        'nonce' => '', // 隨機字符串，不長於32位
        'timestamp' => '', // 時間戳
        'subject' => '', // 訂單名稱，不可為空
        'out_trade_no' => '', // 商戶訂單號
        'total_fee' => '', // 總金額，單位分
        'spbill_create_ip' => '', // 終端IP
        'notify_url' => '', // 通知地址
        'sign_type' => 'MD5', // 簽名類型，默認為MD5
        'sign' => '', // 簽名信息
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'mch_id' => 'number',
        'trade_type' => 'paymentVendorId',
        'timestamp' => 'orderCreateDate',
        'subject' => 'orderId',
        'out_trade_no' => 'orderId',
        'total_fee' => 'amount',
        'spbill_create_ip' => 'ip',
        'notify_url' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'mch_id',
        'trade_type',
        'nonce',
        'timestamp',
        'subject',
        'out_trade_no',
        'total_fee',
        'spbill_create_ip',
        'notify_url',
        'sign_type',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'result_code' => 1,
        'mch_id' => 1,
        'trade_type' => 1,
        'nonce' => 1,
        'timestamp' => 1,
        'out_trade_no' => 1,
        'total_fee' => 1,
        'trade_no' => 1,
        'platform_trade_no' => 1,
        'pay_time' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'SUCCESS';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1098' => 'ALIH5', // 支付寶_手機支付
        '1102' => 'GATEWAY', // 網銀收銀台
        '1111' => 'UPSCAN', // 銀聯_二維
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->verifyPrivateKey();
        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['trade_type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['trade_type'] = $this->bankMap[$this->requestData['trade_type']];
        $this->requestData['nonce'] = md5(uniqid(rand(), true));
        $this->requestData['timestamp'] = strtotime($this->requestData['timestamp']);
        $this->requestData['total_fee'] = round($this->requestData['total_fee'] * 100);

        // 設定加密簽名
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/pay/unifiedorder',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->requestData),
            'header' => ['Content-Type' => 'application/json; charset=utf-8'],
        ];

        $parseData = $this->curlRequest($curlParam);

        if (!isset($parseData['pay_url'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 銀聯二維
        if ($this->options['paymentVendorId'] == 1111) {
            // 跳轉網址為Qrcode圖片，直接於藍色頁面印出
            $html = sprintf('<img src="%s"/>', $parseData['pay_url']);

            $this->setHtml($html);

            return [];
        }

        // 解析跳轉網址
        $urlData = $this->parseUrl($parseData['pay_url']);

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
        $this->verifyPrivateKey();
        $this->payResultVerify();

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) !== '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr = md5($encodeStr);

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $sign = base64_decode($this->options['sign']);

        if (!openssl_verify($encodeStr, $sign, $this->getRsaPublicKey(), OPENSSL_ALGO_SHA256)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['result_code'] !== 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_fee'] != round($entry['amount'] * 100)) {
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

        foreach ($this->encodeParams as $index) {
            if (array_key_exists($index, $this->requestData) && trim($this->requestData[$index]) !== '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

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
     * @return array Response Content
     */
    protected function curlRequest($curlParam)
    {
        $logger = $this->container->get('durian.payment_logger');

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

                if (!$result) {
                    throw new PaymentConnectionException('Empty Payment Gateway response', 180089, $this->getEntryId());
                }

                $parseData = json_decode($result, true);

                // 提交錯誤時非200，錯誤提示在message參數裡
                if ($response->getStatusCode() != 200) {
                    if (isset($parseData['message'])) {
                        throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
                    }

                    throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
                }

                return $parseData;
            } catch (\Exception $e) {
                if (end($curlParam['ip']) == $ip) {
                    throw $e;
                }

                continue;
            }
        }
    }
}
