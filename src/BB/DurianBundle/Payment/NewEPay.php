<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 新E付掃碼
 */
class NewEPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'orderId' => '', // 訂單號
        'merchId' => '', // 商號
        'payWay' => '', // 支付通道
        'curType' => 'CNY', // 幣別(固定值)
        'tranTime' => '', // 交易時間(YmdHis)
        'totalAmt' => '', // 金額(以分為單位)
        'title' => '', // 訂單標題(帶入username)
        'attach' => '', // 訂單描述(非必填)
        'notifyUrl' => '', // 異步通知
        'extend1' => '', // 備用域1(非必填)
        'extend2' => '', // 備用域2(非必填)
        'extend3' => '', // 備用域3(非必填)
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'orderId' => 'orderId',
        'merchId' => 'number',
        'payWay' => 'paymentVendorId',
        'tranTime' => 'orderCreateDate',
        'totalAmt' => 'amount',
        'title' => 'username',
        'notifyUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'orderId',
        'merchId',
        'payWay',
        'curType',
        'tranTime',
        'totalAmt',
        'title',
        'attach',
        'notifyUrl',
        'extend1',
        'extend2',
        'extend3',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'msgData' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '000000';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1090 => 'weixin', // 微信_二維
        1092 => 'alipay', // 支付寶_二維
        1103 => 'qqpay', // QQ_二維
        1104 => 'qqpayH5', // QQ_手機支付
        1107 => 'jdpay', // 京東錢包_二維
        1109 => 'bdpay', // 百度錢包_二維
        1111 => 'unionpay', // 銀聯_二維
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'orderId' => '', // 訂單號
        'extend1' => '', // 備用域1
        'extend2' => '', // 備用域2
        'extend3' => '', // 備用域3
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'orderId' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'orderId',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'msgData' => 1,
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
        if (!array_key_exists($this->requestData['payWay'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外參數設定
        $orderCreateDate = new \DateTime($this->options['orderCreateDate']);
        $this->requestData['payWay'] = $this->bankMap[$this->requestData['payWay']];
        $this->requestData['tranTime'] = $orderCreateDate->format('YmdHis');
        $this->requestData['totalAmt'] = round($this->requestData['totalAmt'] * 100);

        // 設定最後實際傳給支付平台的資料
        $requestParams = [
            'partner' => $this->requestData['merchId'],
            'encryptType' => 'md5',
            'msgData' => base64_encode(json_encode($this->requestData)),
            'signData' => $this->encode(),
        ];

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/pay/v2.0/scan',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($requestParams)),
            'header' => [],
        ];

        $result = json_decode($this->curlRequest($curlParam), true);

        if (!isset($result['msgData'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $msgData = json_decode(base64_decode($result['msgData']), true);

        if (!isset($msgData['respCode'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 訂單提交失敗
        if ($msgData['respCode'] !== '0000') {
            // 提交失敗且有返回錯誤訊息
            if (isset($msgData['respMsg'])) {
                throw new PaymentConnectionException($msgData['respMsg'], 180130, $this->getEntryId());
            }

            // 提交失敗但沒有返回錯誤訊息
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if (!isset($result['signData'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $sign = md5($result['msgData'] . $this->privateKey);

        // 驗簽
        if (strcasecmp($sign, $result['signData'])) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if (!isset($msgData['qrCode'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 手機支付
        if ($this->options['paymentVendorId'] == 1104) {
            // 解析跳轉網址
            $urlData = $this->parseUrl($msgData['qrCode']);

            // Form使用GET才能正常跳轉
            $this->payMethod = 'GET';

            return [
                'post_url' => $urlData['url'],
                'params' => $urlData['params'],
            ];
        }

        $this->setQrcode($msgData['qrCode']);

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

        $sign = md5($this->options['msgData'] . $this->privateKey);

        if (!isset($this->options['signData'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 驗簽
        if (strcasecmp($sign, $this->options['signData'])) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        $msgData = json_decode(base64_decode($this->options['msgData']), true);

        $requiredKeys = ['respCode', 'orderId', 'totalAmount'];

        foreach ($requiredKeys as $requiredKey) {
            if (!isset($msgData[$requiredKey])) {
                throw new PaymentException('No return parameter specified', 180137);
            }
        }

        // 檢查支付狀態
        if ($msgData['respCode'] !== '0000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查訂單號
        if ($msgData['orderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額
        if ($msgData['totalAmount'] != round($entry['amount'] * 100)) {
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

        if (!isset($this->options['number'])) {
            throw new PaymentException('No tracking parameter specified', 180138);
        }

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $trackingRequestParams = [
            'partner' => $this->options['number'],
            'encryptType' => 'md5',
            'msgData' => base64_encode(json_encode($this->trackingRequestData)),
            'signData' => $this->trackingEncode(),
        ];

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/pay/v1.0/payQuery',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($trackingRequestParams),
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

        if (!isset($this->options['number'])) {
            throw new PaymentException('No tracking parameter specified', 180138);
        }

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $trackingRequestParams = [
            'partner' => $this->options['number'],
            'encryptType' => 'md5',
            'msgData' => base64_encode(json_encode($this->trackingRequestData)),
            'signData' => $this->trackingEncode(),
        ];

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/pay/v1.0/payQuery',
            'method' => 'POST',
            'form' => $trackingRequestParams,
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
        $this->verifyPrivateKey();

        $parseData = json_decode($this->options['content'], true);

        $this->trackingResultVerify($parseData);

        $sign = md5($parseData['msgData'] . $this->privateKey);

        if (!isset($parseData['signData'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (strcasecmp($sign, $parseData['signData'])) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        $msgData = json_decode(base64_decode($parseData['msgData']), true);

        if (!isset($msgData['respCode'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 訂單不存在
        if ($msgData['respCode'] === '2001') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        // 訂單處理中
        if ($msgData['respCode'] === '0010') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        // 只有返回 0000 為訂單支付成功
        if ($msgData['respCode'] !== '0000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if (!isset($msgData['orderId']) || !isset($msgData['totalAmount'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 檢查訂單號
        if ($msgData['orderId'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額
        if ($msgData['totalAmount'] != round($this->options['amount'] * 100)) {
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
        $encodeStr = json_encode($this->requestData);
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
        $encodeStr = json_encode($this->trackingRequestData);
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}
