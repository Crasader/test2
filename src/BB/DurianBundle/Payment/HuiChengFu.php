<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 匯誠付
 */
class HuiChengFu extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'service' => 'TRADE.B2C', // 接口名稱
        'version' => '1.0.0.0', // 接口版本
        'merId' => '', // 商戶號
        'tradeNo' => '', // 訂單號
        'tradeDate' => '', // 交易日期 YMD
        'amount' => '', // 支付金額，保留小數點兩位，單位：元
        'notifyUrl' => '', // 通知網址
        'extra' => '', // 支付完成原樣回調，可空
        'summary' => '', // 交易摘要
        'expireTime' => '', // 超時時間，單位：秒，可空
        'clientIp' => '', // 客戶端ip
        'bankId' => '', // 銀行代碼
        'sign' => '', // 簽名
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merId' => 'number',
        'tradeNo' => 'orderId',
        'tradeDate' => 'orderCreateDate',
        'amount' => 'amount',
        'notifyUrl' => 'notify_url',
        'summary' => 'username',
        'clientIp' => 'ip',
        'bankId' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'service',
        'version',
        'merId',
        'typeId',
        'tradeNo',
        'tradeDate',
        'amount',
        'notifyUrl',
        'platform',
        'extra',
        'summary',
        'expireTime',
        'clientIp',
        'bankId',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'service' => 1,
        'merId' => 1,
        'tradeNo' => 1,
        'tradeDate' => 1,
        'opeNo' => 1,
        'opeDate' => 1,
        'amount' => 1,
        'status' => 1,
        'extra' => 1,
        'payTime' => 1,
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
        278 => 'KJZF', // 銀聯在線
        1088 => 'KJZF', // 銀聯在線_手機支付
        1090 => '2', // 微信_二維
        1097 => '2', // 微信_手機支付
        1103 => '3', // QQ_二維
        1104 => '3', // QQ_手機支付
        1111 => '4', // 銀聯_二維
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'service' => 'TRADE.QUERY', // 接口名字，固定值
        'version' => '1.0.0.0', // 接口版本，固定值
        'merId' => '', // 商號戶
        'tradeNo' => '', // 訂單號
        'tradeDate' => '', // 交易日期
        'amount' => '', // 交易金額
        'sign' => '', // 簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merId' => 'number',
        'tradeNo' => 'orderId',
        'tradeDate' => 'orderCreateDate',
        'amount' => 'amount',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'service',
        'version',
        'merId',
        'tradeNo',
        'tradeDate',
        'amount',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *      0: 可不返回的參數
     *      1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'code' => 1,
        'desc' => 1,
        'orderDate' => 0,
        'opeDate' => 0,
        'tradeNo' => 0,
        'opeNo' => 0,
        'exchangeRate' => 0,
        'status' => 0,
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

        // 驗證支付參數
        $this->payVerify();

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['bankId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bankId'] = $this->bankMap[$this->requestData['bankId']];
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);

        $createAt = new \Datetime($this->requestData['tradeDate']);
        $this->requestData['tradeDate'] = $createAt->format('Ymd');

        // 二維支付，需調整參數，typeId與bankId只能擇一使用
        if (in_array($this->options['paymentVendorId'], [1090, 1103, 1111])) {
            $this->requestData['service'] = 'TRADE.SCANPAY';
            $this->requestData['typeId'] = $this->requestData['bankId'];
            unset($this->requestData['bankId']);

            $this->requestData['sign'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/cooperate/gateway.cgi',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = $this->xmlToArray($result);

            if (!isset($parseData['detail']['code']) || !isset($parseData['detail']['desc'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if (trim($parseData['detail']['code']) !== '00') {
                throw new PaymentConnectionException($parseData['detail']['desc'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['detail']['qrCode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode(base64_decode($parseData['detail']['qrCode']));

            return [];
        }

        // 手機支付，需調整參數
        if (in_array($this->options['paymentVendorId'], [1097, 1104])) {
            $this->requestData['service'] = 'TRADE.H5PAY';
            $this->requestData['typeId'] = $this->requestData['bankId'];
            unset($this->requestData['bankId']);
        }

        // 設定加密簽名
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

        // 驗證返回參數
        $this->payResultVerify();

        // 組合參數驗證加密簽名
        $encodeData = [];
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 沒有返回signMsg就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['tradeNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 訂單查詢
     */
    public function paymentTracking()
    {
        $this->verifyPrivateKey();
        $this->trackingVerify();

        // 訂單查詢參數設定
        $this->setTrackingRequestData();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/cooperate/gateway.cgi',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => [],
        ];

        // 取得訂單查詢結果
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
        $this->verifyPrivateKey();
        $this->trackingVerify();

        // 訂單查詢參數設定
        $this->setTrackingRequestData();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/cooperate/gateway.cgi',
            'method' => 'POST',
            'form' => $this->trackingRequestData,
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
        $parseData = $this->parseData($this->options['content']);

        if (!isset($parseData['detail']) || !isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $this->trackingResultVerify($parseData['detail']);

        if (trim($parseData['detail']['code']) !== '00') {
            throw new PaymentConnectionException($parseData['detail']['desc'], 180130, $this->getEntryId());
        }

        $trackingEncodeStr = '<detail>';
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData['detail'])) {
                $trackingEncodeStr .= "<$paymentKey>" . $parseData['detail'][$paymentKey] . "</$paymentKey>";
            }
        }
        $trackingEncodeStr .= '</detail>';
        $trackingEncodeStr .= $this->privateKey;

        if (strcasecmp($parseData['sign'], md5($trackingEncodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['detail']['status'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['detail']['tradeNo'] !== $this->options['orderId']) {
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

        // 參數存在都要納入加密
        foreach ($this->encodeParams as $index) {
            if (isset($this->requestData[$index])) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
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

        // 參數存在都要納入加密
        foreach ($this->trackingEncodeParams as $index) {
            if (isset($this->trackingRequestData[$index])) {
                $encodeData[$index] = $this->trackingRequestData[$index];
            }
        }

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }

    /**
     * 分析支付平台回傳的入款查詢參數
     *
     * @param string $content xml的回傳格式
     * @return array
     */
    private function parseData($content)
    {
        $parseData = $this->xmlToArray($content);

        return $parseData;
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

        $orderDate = new \DateTime($this->trackingRequestData['tradeDate']);
        $this->trackingRequestData['tradeDate'] = $orderDate->format('Ymd');

        $this->trackingRequestData['amount'] = sprintf('%.2f', $this->trackingRequestData['amount']);

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();
    }
}
