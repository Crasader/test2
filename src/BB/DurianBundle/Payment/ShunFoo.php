<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 順付
 */
class ShunFoo extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'parter' => '', // 商號
        'type' => '', // 銀行類型
        'value' => '', // 金額，單位:元
        'orderid' => '', // 訂單號
        'callbackurl' => '', // 異步通知url, 不能串參數
        'hrefbackurl' => '', // 同步通知url，非必填
        'payerIp' => '', // 支付用戶ip，非必填
        'attach' => '', // 備註訊息，非必填
        'sign' => '', // md5簽名
        'agent' => '', // 代理ID，非必填
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'parter' => 'number',
        'orderid' => 'orderId',
        'value' => 'amount',
        'callbackurl' => 'notify_url',
        'type' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'parter',
        'type',
        'value',
        'orderid',
        'callbackurl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'orderid' => 1,
        'opstate' => 1,
        'ovalue' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'opstate=0';

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'parter' => '', // 商號
        'orderid' => '', // 訂單號
        'sign' => '', // md5簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'parter' => 'number',
        'orderid' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'orderid',
        'parter',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'orderid' => 1,
        'opstate' => 1,
        'ovalue' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '967', // 中國工商銀行
        '2' => '981', // 交通銀行
        '3' => '964', // 中國農業銀行
        '4' => '965', // 中國建設銀行
        '5' => '970', // 招商銀行
        '6' => '980', // 中國民生銀行
        '8' => '977', // 上海浦東發展銀行
        '9' => '989', // 北京銀行
        '10' => '972', // 興業銀行
        '11' => '962', // 中信銀行
        '12' => '986', // 中國光大銀行
        '13' => '982', // 華夏銀行
        '14' => '985', // 廣東發展銀行
        '15' => '978', // 平安銀行
        '16' => '971', // 中國郵政
        '17' => '963', // 中國銀行
        '19' => '975', // 上海銀行
        '217' => '988', // 渤海银行
        '220' => '983', // 杭州银行
        '221' => '968', // 浙商银行
        '223' => '987', // 東亞银行
        '226' => '979', // 南京银行
        '227' => '984', // 廣州市農村信用合作社
        '228' => '976', // 上海農村商業銀行
        '231' => '973', // 順德農村信用合作社
        '233' => '969', // 浙江稠州商業銀行
        '234' => '990', // 北京農村商業銀行
        '297' => '993', // 財付通
        '1090' => '1004', // 微信支付_二維
        '1092' => '992', // 支付寶_二維
        '1097' => '1007', // 微信支付_手機支付
        '1098' => '1006', // 支付寶_手機支付
        '1103' => '1008', // QQ_二維
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

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['type'] = $this->bankMap[$this->requestData['type']];

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        return $this->requestData;
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

        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 沒有sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 請求參數無效
        if ($this->options['opstate'] == '-1') {
            throw new PaymentConnectionException('Invalid pay parameters', 180129, $this->getEntryId());
        }

        // 簽名錯誤
        if ($this->options['opstate'] == '-2') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Merchant sign error',
                180127,
                $this->getEntryId()
            );
        }

        if ($this->options['opstate'] !== '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderid'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['ovalue'] != $entry['amount']) {
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

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'GET',
            'uri' => '/search.aspx',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => [],
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

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/search.aspx?' . http_build_query($this->trackingRequestData),
            'method' => 'GET',
            'headers' => [
                'Host' => $this->options['verify_url'],
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

        $parseData = [];

        parse_str($this->options['content'], $parseData);

        $this->trackingResultVerify($parseData);

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 沒有sign丟例外
        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 驗證簽名
        if ($parseData['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 請求參數無效
        if ($parseData['opstate'] == '3') {
            throw new PaymentConnectionException('Submit the parameter error', 180075, $this->getEntryId());
        }

        // 簽名錯誤
        if ($parseData['opstate'] == '2') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Merchant sign error',
                180127,
                $this->getEntryId()
            );
        }

        // 商戶訂單號無效
        if ($parseData['opstate'] == '1') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        // 不等於0即為支付失敗
        if ($parseData['opstate'] !== '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 查詢成功返回商戶訂單號
        if ($parseData['orderid'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 查詢成功返回訂單金額
        if ($parseData['ovalue'] != $this->options['amount']) {
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

        // 組織加密簽名
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
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

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}
