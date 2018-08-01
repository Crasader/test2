<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 中鐵通付
 */
class CRPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchant_no' => '', // 商戶號
        'method' => 'unified.trade.pay', // 方法名，固定值
        'version' => '1.0', // 版本號
        'sign' => '', // 簽名
        'out_trade_no' => '', // 商戶訂單號
        'timestamp' => '', // 發送請求時間戳
        'amount' => '', // 金額，單位分
        'body' => '', // 商品描述，設定username方便業主比對
        'remark' => '', // 備註信息，非必填
        'return_url' => '', // 同步通知地址，非必填
        'notify_url' => '', // 異步通知地址
        'way' => '', // 支付渠道類型編碼

    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchant_no' => 'number',
        'out_trade_no' => 'orderId',
        'timestamp' => 'orderCreateDate',
        'amount' => 'amount',
        'body' => 'username',
        'notify_url' => 'notify_url',
        'way' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchant_no',
        'method',
        'version',
        'out_trade_no',
        'timestamp',
        'amount',
        'body',
        'remark',
        'return_url',
        'notify_url',
        'way',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'code' => 1,
        'msg' => 1,
        'amount' => 1,
        'trade_no' => 1,
        'out_trade_no' => 1,
        'status' => 1,
        'remark' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => 'wxQR', // 微信
        '1092' => 'alipayQR', // 支付寶
        '1098' => 'alipayH5', // 支付寶_手機支付
        '1103' => 'qqQR', // QQ_二維
        '1104' => 'qqH5', // QQ_手機支付
        '1107' => 'jdQR', // 京東錢包_二维
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'merchant_no' => '', // 商戶號
        'method' => 'unified.trade.payquery', // 方法名，固定值
        'version' => '1.0', // 版本號
        'sign' => '', // 簽名
        'out_trade_no' => '', // 商戶訂單號
        'trade_no' => '', // 平台流水號
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchant_no' => 'number',
        'out_trade_no' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'merchant_no',
        'method',
        'version',
        'out_trade_no',
        'trade_no',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'code' => 1,
        'msg' => 1,
        'out_trade_no' => 1,
        'trade_no' => 1,
        'amount' => 1,
        'order_time' => 1,
        'finish_time' => 0,
        'status' => 1,
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
        if (!array_key_exists($this->requestData['way'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['amount'] = round($this->requestData['amount'] * 100);
        $this->requestData['way'] = $this->bankMap[$this->requestData['way']];
        $date = new \DateTime($this->requestData['timestamp']);
        $this->requestData['timestamp'] = $date->getTimestamp() . '000';

        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/payapi/gateway',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->requestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['code']) || !isset($parseData['msg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['code'] !== '0000') {
            throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['code_url'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 手機支付
        if (in_array($this->options['paymentVendorId'], [1098, 1104])) {
            return ['act_url' => $parseData['code_url']];
        }

        $this->setQrcode($parseData['code_url']);

        return [];
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

        if ($this->options['code'] !== '0000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        $encodeStr = '';

        ksort($this->decodeParams);

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] != '') {
                $encodeStr .= $this->options[$paymentKey];
            }
        }

        $encodeStr .= $this->privateKey;

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] != $entry['id']) {
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
            'method' => 'POST',
            'uri' => '/payapi/gateway',
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
            'path' => '/payapi/gateway',
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
        // 驗證私鑰
        $this->verifyPrivateKey();

        $parseData = json_decode($this->options['content'], true);

        if (!isset($parseData['code']) || !isset($parseData['msg'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['code'] !== '0000') {
            throw new PaymentConnectionException($parseData['msg'], 180123, $this->getEntryId());
        }

        $this->trackingResultVerify($parseData);

        $encodeStr = '';

        ksort($this->trackingDecodeParams);

        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData) && $parseData[$paymentKey] != '') {
                $encodeStr .= $parseData[$paymentKey];
            }
        }

        $encodeStr .= $this->privateKey;

        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['status'] === '00') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData['status'] !== '01' && $parseData['status'] !== '03') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['out_trade_no'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['amount'] != round($this->options['amount'] * 100)) {
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
        $encodeStr = '';

        sort($this->encodeParams);

        foreach ($this->encodeParams as $key) {
            if ($this->requestData[$key] != '') {
                $encodeStr .= $this->requestData[$key];
            }
        }

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
        $encodeStr = '';

        sort($this->trackingEncodeParams);

        // 加密設定
        foreach ($this->trackingEncodeParams as $key) {
            if ($this->trackingRequestData[$key] != '') {
                $encodeStr .= $this->trackingRequestData[$key];
            }
        }

        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}
