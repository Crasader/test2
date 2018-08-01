<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 鑫寶支付
 */
class XinBao extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'order_number' => '', // 訂單號
        'merchant_code' => '', // 商戶編碼
        'pay_type' => '', // 支付方式
        'amount' => '', // 金額，單位元，小數點後兩位
        'asyn_notify_url' => '', // 異步通知地址，不能帶參數
        'syn_notify_url' => '', // 同步通知地址，不能帶參數
        'attach' => '', // 附加信息，選填
        'answer_type' => 'RESPONSE', // 響應類型，選填，REDIRECT: 跳轉, RESPONSE: 同步響應(返回二維碼)
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchant_code' => 'number',
        'amount' => 'amount',
        'order_number' => 'orderId',
        'asyn_notify_url' => 'notify_url',
        'syn_notify_url' => 'notify_url',
        'pay_type' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'order_number',
        'merchant_code',
        'pay_type',
        'amount',
        'asyn_notify_url',
        'syn_notify_url',
        'answer_type',
        'signKey',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchant_code' => 1,
        'code' => 1,
        'msg' => 1,
        'order_number' => 1,
        'amount' => 1,
        'attach' => 0,
        'signKey' => 0,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'ok';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => 'WECHATQR', // 微信_二維
        '1092' => 'ALIPAYQR', // 支付寶_二維
        '1098' => 'ALIPAYWAP', // 支付寶WAP
        '1102' => 'EBANK', // 收銀台
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'merchant_code' => '', // 商戶編碼
        'merchant_no' => '', // 訂單號
        'sign' => '', // 簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchant_code' => 'number',
        'merchant_no' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'merchant_code',
        'merchant_no',
        'signKey',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'no' => 1,
        'merchant_code' => 1,
        'merchant_no' => 1,
        'pay_type_code' => 1,
        'money' => 1,
        'syn_notify_url' => 1,
        'asyn_notify_url' => 1,
        'create_time' => 1,
        'status' => 1,
        'in_rate_money' => 1,
        'real_money' => 1,
        'success_time' => 1,
        'attach' => 1,
        'signKey' => 0,
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

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['pay_type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);
        $this->requestData['pay_type'] = $this->bankMap[$this->requestData['pay_type']];

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        // 二維支付(微信、支付寶)
        if (in_array($this->options['paymentVendorId'], [1090, 1092])) {
            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'GET',
                'uri' => '/pay',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => urldecode(http_build_query($this->requestData)),
                'header' => []
            ];

            $result = $this->curlRequest($curlParam);

            $parseData = json_decode($result, true);

            if (!isset($parseData['code']) || !isset($parseData['msg']) || !isset($parseData['r_data'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['code'] != '1') {
                throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
            }

            $this->setQrcode($parseData['r_data']);

            return [];
        }

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

        // 驗證回傳參數
        $this->payResultVerify();

        $encodeStr = '';

        ksort($this->decodeParams);

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            // 排除signKey需另外給值帶入，其他參數都需納簽
            if (array_key_exists($paymentKey, $this->options) && $paymentKey != 'signKey') {
                $encodeStr .= $this->options[$paymentKey];
            }

            if ($paymentKey == 'signKey') {
                $encodeStr .= $this->privateKey;
            }
        }

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified' , 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['code'] == '0') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($this->options['code'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_number'] != $entry['id']) {
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
        // 驗證私鑰
        $this->verifyPrivateKey();

        // 驗證訂單查詢參數
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        // 執行訂單查詢
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'GET',
            'uri' => '/s/order',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => [],
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if(!isset($parseData['code']) || !isset($parseData['msg']) || !isset($parseData['r_data'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['code'] != '1') {
            throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
        }

        $resultData = $parseData['r_data'];

        $this->trackingResultVerify($resultData);

        $encodeStr = '';

        ksort($this->trackingDecodeParams);

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if ($paymentKey == 'signKey') {
                $encodeStr .= $this->privateKey;
            }

            if (array_key_exists($paymentKey, $resultData)) {
                $encodeStr .= $resultData[$paymentKey];
            }
        }

        if (!isset($resultData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($resultData['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($resultData['status'] == '0') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($resultData['status'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($resultData['merchant_no'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($resultData['money'] != $this->options['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
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

        // 驗證訂單查詢參數
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
            'path' => '/s/order?' . http_build_query($this->trackingRequestData),
            'method' => 'GET',
            'headers' => [
                'Host' => $this->options['verify_url']
            ]
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

        $resultData = $parseData['r_data'];

        $this->trackingResultVerify($resultData);

        $encodeStr = '';

        ksort($this->trackingDecodeParams);

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if ($paymentKey == 'signKey') {
                $encodeStr .= $this->privateKey;
            }

            if (array_key_exists($paymentKey, $resultData)) {
                $encodeStr .= $resultData[$paymentKey];
            }
        }

        if (!isset($resultData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($resultData['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($resultData['status'] == '0') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($resultData['status'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($resultData['merchant_no'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($resultData['money'] != $this->options['amount']) {
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

        // 依value1value2valueN之後做md5
        foreach ($this->encodeParams as $key) {
            // 密鑰需要另外帶入，並且跳過之後動作
            if ($key == 'signKey') {
                $encodeStr .= $this->privateKey;

                continue;
            }

            $encodeStr .= $this->requestData[$key];
        }

        return md5($encodeStr);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeStr ='';

        sort($this->trackingEncodeParams);

        // 依value1value2valueN之後做md5
        foreach ($this->trackingEncodeParams as $key) {
            // 密鑰需要另外帶入，並且跳過之後動作
            if ($key == 'signKey') {
                $encodeStr .= $this->privateKey;

                continue;
            }

            $encodeStr .= $this->trackingRequestData[$key];
        }

        return md5($encodeStr);
    }
}
