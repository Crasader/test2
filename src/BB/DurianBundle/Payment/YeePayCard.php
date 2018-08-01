<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 易寶(易游酷)點卡
 */
class YeePayCard extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'bizType' => 'STANDARD', // 業務類型，固定值
        'merchantNo' => '', // 商號
        'merchantOrderNo' => '', // 商戶訂單號
        'requestAmount' => '', // 訂單金額，精確到分
        'url' => '', // 回調地址
        'cardCode' => '', // 支付渠道編碼
        'productName' => '', // 產品名稱，可空
        'productType' => '', // 產品類型，可空
        'productDesc' => '', // 產品描述，可空
        'extInfo' => '', // 商戶擴展訊息，可空
        'hmac' => '', // 簽名數據
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantNo' => 'number',
        'url' => 'notify_url',
        'merchantOrderNo' => 'orderId',
        'requestAmount' => 'amount',
        'cardCode' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'bizType',
        'merchantNo',
        'merchantOrderNo',
        'requestAmount',
        'url',
        'cardCode',
        'productName',
        'productType',
        'productDesc',
        'extInfo',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'bizType' => 1,
        'result' => 1,
        'merchantNo' => 1,
        'merchantOrderNo' => 1,
        'successAmount' => 1,
        'cardCode' => 1,
        'noticeType' => 1,
        'extInfo' => 1,
        'cardNo' => 1,
        'cardStatus' => 1,
        'cardReturnInfo' => 1,
        'cardIsbalance' => 1,
        'cardBalance' => 1,
        'cardSuccessAmount' => 1,
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
        1000 => 'MOBILE', // 移動儲值卡
        1001 => 'UNICOM', // 聯通儲值卡
        1002 => 'TELECOM', // 電信儲值卡
        1073 => 'JW', // 駿網一卡通
        1074 => 'SD', // 盛大卡
        1075 => 'ZT', // 征途卡
        1076 => 'QQ', // Q幣卡
        1077 => 'JY', // 久游卡
        1078 => 'WY', // 網易卡
        1079 => 'WM', // 完美卡
        1080 => 'SH', // 搜狐卡
        1081 => 'ZY', // 縱游一卡通
        1082 => 'TX', // 天下一卡通
        1083 => 'TH', // 天宏一卡通
        1084 => 'CARDTW' // 32一卡通
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'merchantNo' => '', // 商戶編號
        'merchantOrderNo' => '', // 商戶訂單號
        'hmac' => '', // 簽名數據
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchantNo' => 'number',
        'merchantOrderNo' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'merchantNo',
        'merchantOrderNo',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'bizType' => 1,
        'result' => 1,
        'merchantNo' => 1,
        'merchantOrderNo' => 1,
        'successAmount' => 1,
        'cardCode' => 1,
        'noticeType' => 1,
        'extInfo' => 1,
        'cardNo' => 1,
        'cardStatus' => 1,
        'cardReturnInfo' => 1,
        'cardIsbalance' => 1,
        'cardBalance' => 1,
        'cardSuccessAmount' => 1,
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
        if (!array_key_exists($this->requestData['cardCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['cardCode'] = $this->bankMap[$this->requestData['cardCode']];
        $this->requestData['requestAmount'] = sprintf('%.2f', $this->requestData['requestAmount']);

        // 設定支付平台需要的加密串
        $this->requestData['hmac'] = $this->encode();

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

        $encodeStr = '';

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }

        // 沒有hmac就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['hmac'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 驗證簽名
        if ($this->options['hmac'] != hash_hmac('md5', $encodeStr, $this->privateKey)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['cardStatus'] != '0' || $this->options['result'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['merchantOrderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['successAmount'] != $entry['amount']) {
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
        $this->trackingRequestData['hmac'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/yeex-iface-app/queryOrder',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => [],
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['code'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['code'] == '120000') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        if ($parseData['code'] == '120001') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData['code'] == '100100') {
            throw new PaymentConnectionException('Submit the parameter error', 180075, $this->getEntryId());
        }

        if ($parseData['code'] == '100300') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Merchant sign error',
                180127,
                $this->getEntryId()
            );
        }

        if ($parseData['code'] == '100401') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Merchant is not exist',
                180086,
                $this->getEntryId()
            );
        }

        if ($parseData['code'] != '000000') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        // 交易成功返回data
        if (!isset($parseData['data'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $this->trackingResultVerify($parseData['data']);

        $encodeStr = '';

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData['data'])) {
                $encodeStr .= $parseData['data'][$paymentKey];
            }
        }

        // 沒有hmac丟例外，其他的參數在組織加密串的時候驗證
        if (!isset($parseData['data']['hmac'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 驗證簽名
        if ($parseData['data']['hmac'] != hash_hmac('md5', $encodeStr, $this->privateKey)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 訂單異常
        if ($parseData['data']['result'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['data']['merchantOrderNo'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['data']['successAmount'] != $this->options['amount']) {
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
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 設定加密簽名
        $this->trackingRequestData['hmac'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/yeex-iface-app/queryOrder',
            'method' => 'POST',
            'form' => $this->trackingRequestData,
            'headers' => [
                'Host' => $this->options['verify_url'],
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

        if (!isset($parseData['code'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['code'] == '120000') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        if ($parseData['code'] == '120001') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData['code'] == '100100') {
            throw new PaymentConnectionException('Submit the parameter error', 180075, $this->getEntryId());
        }

        if ($parseData['code'] == '100300') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Merchant sign error',
                180127,
                $this->getEntryId()
            );
        }

        if ($parseData['code'] == '100401') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Merchant is not exist',
                180086,
                $this->getEntryId()
            );
        }

        if ($parseData['code'] != '000000') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        // 交易成功返回data
        if (!isset($parseData['data'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $this->trackingResultVerify($parseData['data']);

        $encodeStr = '';

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData['data'])) {
                $encodeStr .= $parseData['data'][$paymentKey];
            }
        }

        // 沒有hmac丟例外，其他的參數在組織加密串的時候驗證
        if (!isset($parseData['data']['hmac'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 驗證簽名
        if ($parseData['data']['hmac'] != hash_hmac('md5', $encodeStr, $this->privateKey)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 訂單異常
        if ($parseData['data']['result'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['data']['merchantOrderNo'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['data']['successAmount'] != $this->options['amount']) {
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

        // 加密設定
        foreach ($this->encodeParams as $index) {
            $encodeStr .= $this->requestData[$index];
        }

        return hash_hmac('md5', $encodeStr, $this->privateKey);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeStr = '';

        // 加密設定
        foreach ($this->trackingEncodeParams as $index) {
            $encodeStr .= $this->trackingRequestData[$index];
        }

        return hash_hmac('md5', $encodeStr, $this->privateKey);
    }
}
