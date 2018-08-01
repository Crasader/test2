<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 易寶微信支付
 */
class YeePayWeiXin extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantNo' => '', // 商號
        'merchantOrderno' => '', // 商戶訂單號
        'requestAmount' => '', // 訂單金額，精確到分
        'noticeSysaddress' => '', // 異步通知URL
        'noticeWebaddress' => '', // 頁面通知URL，非必填
        'memberNo' => '', // 商戶id
        'memberGoods' => '', // 商品名稱，非必填
        'payType' => 'WX', // 支付類型，固定值
        'hmac' => '', // 加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantNo' => 'number',
        'noticeSysaddress' => 'notify_url',
        'merchantOrderno' => 'orderId',
        'requestAmount' => 'amount',
        'memberNo' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantNo',
        'merchantOrderno',
        'requestAmount',
        'noticeSysaddress',
        'noticeWebaddress',
        'memberNo',
        'memberGoods',
        'payType',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'reCode' => 1,
        'merchantNo' => 1,
        'merchantOrderno' => 1,
        'result' => 1,
        'payType' => 1,
        'memberGoods' => 1,
        'amount' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'SUCCESS';

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'merchantNo' => '', // 商號
        'merchantOrderNo' => '', // 訂單號
        'hmac' => '', // 加密簽名
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
        'reCode' => 1,
        'merchantNo' => 1,
        'merchantOrderno' => 1,
        'result' => 1,
        'payType' => 1,
        'memberGoods' => 1,
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

        // 額外的參數設定
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

        if ($this->options['reCode'] != '1' || $this->options['result'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['merchantOrderno'] != $entry['id']) {
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
            'uri' => '/fourth-app/queryForMerchant',
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

        if ($parseData['code'] == '120001') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData['code'] == '120000') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
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

        // 訂單處理中
        if ($parseData['data']['result'] == 'DEAL') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        // 訂單異常
        if ($parseData['data']['result'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
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