<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 哆啦寶
 */
class DuoLaBao extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'customerNum' => '', // 商戶編號
        'amount' => '', // 訂單金額，單位:元
        'callbackUrl' => '', // 返回網址
        'requestNum' => '', // 流水號(訂單號)
        'shopNum' => '', // 店鋪編號
        'source' => 'API', // 定值:API
        'machineNum' => '', // 機具序列號，可空
        'tableNum' => '', // 桌號，可空
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'customerNum' => 'number',
        'amount' => 'amount',
        'callbackUrl' => 'notify_url',
        'requestNum' => 'orderId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'timestamp',
        'path',
        'body',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'requestNum' => 1,
        'orderNum' => 0,
        'status' => 1,
        'completeTime' => 1,
        'timestamp' => 1,
        'token' => 1,
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'customerNum' => '', // 商戶號
        'shopNum' => '', // 店鋪編號
        'orderNum' => '', // 訂單號
        'requestNum' => '', // 流水號
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'customerNum' => 'number',
        'requestNum' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'timestamp',
        'path',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'customerName' => 1,
        'requestNum' => 1,
        'orderNum' => 1,
        'source' => 1,
        'bussinessType' => 1,
        'status' => 1,
        'type' => 1,
        'completeTime' => 1,
        'orderAmount' => 1,
        'refundTime' => 0,
        'payRecordList' => 1,
        'errorCode' => 0,
        'errorMsg' => 0,
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

        $this->options['notify_url'] = sprintf(
            '%s?payment_id=%s',
            $this->options['notify_url'],
            $this->options['paymentGatewayId']
        );

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 取得商家附加設定值
        $names = ['accessKey', 'ownerNum'];
        $merchantExtras = $this->getMerchantExtraValue($names);

        // 額外的參數設定
        $this->requestData['shopNum'] = $merchantExtras['ownerNum'];
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);
        $this->requestData['body'] = json_encode($this->requestData);
        $this->requestData['timestamp'] = strtotime($this->options['orderCreateDate']);
        $this->requestData['path'] = '/v1/customer/order/payurl/create';

        // 設定支付平台需要的加密串
        $token = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $header = [
            'Content-Type' => 'application/json',
            'accesskey' => $merchantExtras['accessKey'],
            'timestamp' => $this->requestData['timestamp'],
            'token' => $token,
        ];

        $curlParam = [
            'method' => 'POST',
            'uri' => $this->requestData['path'],
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => $this->requestData['body'],
            'header' => $header,
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['result'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['result'] != 'success' && isset($parseData['error']['errorCode'])) {
            throw new PaymentConnectionException($parseData['error']['errorCode'], 180130, $this->getEntryId());
        }

        if ($parseData['result'] != 'success') {
            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        if (!isset($parseData['data']['url'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $this->setQrcode($parseData['data']['url']);

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

        $this->payResultVerify();

        $encodeStr = "secretKey={$this->privateKey}&timestamp={$this->options['timestamp']}";

        if ($this->options['token'] != strtoupper(sha1($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['requestNum'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }
    }

    /**
     * 訂單查詢
     */
    public function paymentTracking()
    {
        $this->verifyPrivateKey();

        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 取得商家附加設定值
        $names = ['accessKey', 'ownerNum'];
        $merchantExtras = $this->getMerchantExtraValue($names);

        // 額外的參數設定
        $this->trackingRequestData['shopNum'] = $merchantExtras['ownerNum'];
        $this->trackingRequestData['timestamp'] = strtotime($this->options['orderCreateDate']);
        $customerNum = $this->trackingRequestData['customerNum']; // 商戶號
        $shopNum = $this->trackingRequestData['shopNum']; // 店鋪編號
        $requestNum = $this->trackingRequestData['requestNum']; // 訂單號
        $this->trackingRequestData['path'] = "/v1/customer/order/payresult/{$customerNum}/{$shopNum}/{$requestNum}";

        // 設定加密簽名
        $token = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $header = [
            'accesskey' => $merchantExtras['accessKey'],
            'timestamp' => $this->trackingRequestData['timestamp'],
            'token' => $token,
        ];

        $curlParam = [
            'method' => 'GET',
            'uri' => $this->trackingRequestData['path'],
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => '',
            'header' => $header,
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['result'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['result'] != 'success' && isset($parseData['error']['errorCode'])) {
            throw new PaymentConnectionException($parseData['error']['errorCode'], 180123, $this->getEntryId());
        }

        if ($parseData['result'] != 'success') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        if (!isset($parseData['data'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $this->trackingResultVerify($parseData['data']);

        // 訂單未支付
        if ($parseData['data']['status'] == 'INIT') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 支付失敗
        if ($parseData['data']['status'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['data']['requestNum'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['data']['orderAmount'] != $this->options['amount']) {
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

        $encodeData['secretKey'] = $this->privateKey;

        foreach ($this->encodeParams as $paymentKey) {
            $encodeData[$paymentKey] = $this->requestData[$paymentKey];
        }

        // 依照secretKey=value1&timestamp=value2&path=value3&body=value4順序加密
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(sha1($encodeStr));
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeData = [];

        $encodeData['secretKey'] = $this->privateKey;

        foreach ($this->trackingEncodeParams as $paymentKey) {
            $encodeData[$paymentKey] = $this->trackingRequestData[$paymentKey];
        }

        // 依照secretKey=value1&timestamp=value2&path=value3順序加密
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(sha1($encodeStr));
    }
}
