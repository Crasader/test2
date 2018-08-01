<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 輕易付
 */
class ChingYiPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => 'V2.0.0.0', // 版本號固定值
        'merNo' => '', // 商戶號
        'netway' => '', // 支付方式
        'random' => '', // 隨機數
        'orderNum' => '', // 訂單號
        'amount' => '', // 金額，單位:分
        'goodsName' => '', // 商品名稱
        'callBackUrl' => '', // 異步通知網址
        'callBackViewUrl' => '', // 支付成功轉跳網址
        'charset' => 'UTF-8', // 編碼格式
        'sign' => '', // 簽名，字母大寫
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merNo' => 'number',
        'netway' => 'paymentVendorId',
        'orderNum' => 'orderId',
        'amount' => 'amount',
        'goodsName' => 'username',
        'callBackUrl' => 'notify_url',
        'callBackViewUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'merNo',
        'netway',
        'random',
        'orderNum',
        'amount',
        'goodsName',
        'callBackUrl',
        'callBackViewUrl',
        'charset',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *      0: 可不返回的參數
     *      1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merNo' => 1,
        'netway' => 1,
        'orderNum' => 1,
        'amount' => 1,
        'goodsName' => 1,
        'payResult' => 1,
        'payDate' => 1,
    ];

    /**
     * 對外到支付平台的提交子域
     *
     * @var array
     */
    protected $scanSubDomain = [
        '1090' => 'wx', // 微信_二維
        '1092' => 'zfb', // 支付寶_二維
        '1097' => 'wxwap', // 微信_手機支付
        '1098' => 'zfbwap', // 支付寶_手機支付
        '1103' => 'qq', // QQ_二維
        '1104' => 'qqwap', // QQ_手機支付
        '1107' => 'jd', // 京東_二維
        '1109' => 'baidu', // 百度_二維
        '1111' => 'unionpay', // 銀聯_二維
    ];

    /**
     * 應答機制
     *
     * @var string
     */
    protected $msg = '0';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => 'WX',
        '1092' => 'ZFB',
        '1097' => 'WX_WAP',
        '1098' => 'ZFB_WAP',
        '1103' => 'QQ',
        '1104' => 'QQ_WAP',
        '1107' => 'JD',
        '1109' => 'BAIDU',
        '1111' => 'UNION_WALLET',
    ];

    /**
     * 查詢的時候要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'merNo' => '', // 商戶號
        'netway' => '', // 支付方式
        'orderNum' => '', // 訂單號
        'amount' => '', // 金額，單位:分
        'goodsName' => '', // 商品名稱
        'payDate' => '', // 交易日期
        'sign' => '', // 簽名，英文大寫
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'merNo',
        'netway',
        'orderNum',
        'amount',
        'goodsName',
        'payDate',
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merNo' => 'number',
        'netway' => 'paymentVendorId',
        'orderNum' => 'orderId',
        'amount' => 'amount',
        'payDate' => 'orderCreateDate',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'merNo' => 0,
        'msg' => 1,
        'stateCode' => 1,
        'orderNum' => 0,
        'payStateCode' => 0,
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @var array
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
        if (!array_key_exists($this->requestData['netway'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['random'] = strval(rand(0, 9999));
        $this->requestData['amount'] = strval(round($this->requestData['amount'] * 100));
        $this->requestData['netway'] = $this->bankMap[$this->requestData['netway']];

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        // 組織對外提交host網址
        $subDomain = $this->scanSubDomain[$this->options['paymentVendorId']];
        $hostUrl = 'payment.http.' . $subDomain . '.' . $this->options['postUrl'];

        $curlParam = [
            'method' => 'POST',
            'uri' => '/api/pay.action',
            'ip' => $this->options['verify_ip'],
            'host' => $hostUrl,
            'param' => 'data=' . json_encode($this->requestData, JSON_UNESCAPED_SLASHES),
            'header' => ['Port' => 90],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['stateCode']) || !isset($parseData['msg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['stateCode'] != '00') {
            throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['qrcodeUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1107, 1109, 1111])) {
            $this->setQrcode($parseData['qrcodeUrl']);

            return [];
        }

        // 微信手機返回網址須解析後Form以GET方式傳送才能使用
        if ($this->options['paymentVendorId'] == 1097) {
            $parseUrl = parse_url($parseData['qrcodeUrl']);

            $parseUrlValues = [
                'scheme',
                'host',
                'path',
            ];

            foreach ($parseUrlValues as $key) {
                if (!isset($parseUrl[$key])) {
                    throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
                }
            }

            $params = [];

            if (isset($parseUrl['query'])) {
                parse_str($parseUrl['query'], $params);
            }

            $postUrl = sprintf(
                '%s://%s%s',
                $parseUrl['scheme'],
                $parseUrl['host'],
                $parseUrl['path']
            );

            // Form使用GET才能正常跳轉
            $this->payMethod = 'GET';

            return [
                'post_url' => $postUrl,
                'params' => $params,
            ];
        }

        return ['act_url' => $parseData['qrcodeUrl']];
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

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = json_encode($encodeData) . $this->privateKey;

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['payResult'] != '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNum'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != round($entry['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 訂單查詢
     */
    public function paymentTracking()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();
        $this->trackingVerify();
        $this->setTrackingRequestData();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/api/queryPayResult.action',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => 'data=' . json_encode($this->trackingRequestData),
            'header' => ['Port' => 90],
        ];

        $this->options['content'] = $this->curlRequest($curlParam);

        $this->paymentTrackingVerify();
    }

    /**
     * 取得訂單查詢時需要的參數
     *
     * @return array
     */
    public function getPaymentTrackingData()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();
        $this->trackingVerify();
        $this->setTrackingRequestData();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/api/queryPayResult.action',
            'method' => 'POST',
            'form' => 'data=' . json_encode($this->trackingRequestData),
            'headers' => [
                'Host' => $this->options['verify_url'],
                'Port' => 90,
            ],
        ];

        return $curlParam;
    }

    /**
     * 驗證訂單查詢是否成功
     */
    public function paymentTrackingVerify()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        $parseData = json_decode($this->options['content'], true);

        $this->trackingResultVerify($parseData);

        // 訂單查詢失敗
        if ($parseData['stateCode'] != '00') {
            throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
        }

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_UNICODE);
        $encodeStr .= $this->privateKey;

        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if (!isset($parseData['payStateCode'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['payStateCode'] == '99') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($parseData['payStateCode'] != '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if (!isset($parseData['orderNum'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['orderNum'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
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
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);

        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_SLASHES);
        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeData = [];

        // 加密設定
        foreach ($this->trackingEncodeParams as $index) {
            $encodeData[$index] = $this->trackingRequestData[$index];
        }

        ksort($encodeData);

        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_SLASHES);
        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }

    /**
     * 訂單查詢參數設定
     */
    private function setTrackingRequestData()
    {
        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 查詢額外參數設定
        $this->trackingRequestData['netway'] = $this->bankMap[$this->trackingRequestData['netway']];
        $this->trackingRequestData['payDate'] = date('Y-m-d', strtotime($this->trackingRequestData['payDate']));
        $this->trackingRequestData['amount'] = strval(round($this->trackingRequestData['amount'] * 100));

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();
    }
}
