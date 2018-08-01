<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 易游酷
 */
class YiYouKu extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantNo' => '', // 商號
        'merchantOrderno' => '', // 訂單號
        'requestAmount' => '', // 金額，精確到小數後兩位
        'noticeSysaddress' => '', // 異步通知URL
        'memberNo' => '', // 用戶id
        'memberGoods' => '', // 商品名稱，非必填
        'payType' => '', // 支付類型
        'hmac' => '', // 加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantNo' => 'number',
        'merchantOrderno' => 'orderId',
        'requestAmount' => 'amount',
        'noticeSysaddress' => 'notify_url',
        'memberNo' => 'username',
        'payType' => 'paymentVendorId',
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
        'memberNo',
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
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1090 => 'WX', // 微信支付
        1092 => 'ALIPAY', // 支付寶
        1097 => 'WXWAP', // 微信_手機支付
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
        $this->verifyPrivateKey();

        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['payType'], $this->bankMap)) {
            throw new PaymentException(
                'PaymentVendor is not supported by PaymentGateway',
                180066
            );
        }

        // 額外的參數設定
        $this->requestData['payType'] = $this->bankMap[$this->requestData['payType']];
        $this->requestData['requestAmount'] = sprintf('%.2f', $this->requestData['requestAmount']);

        $this->requestData['hmac'] = $this->encode();

        $curlParam = [
            'method' => 'POST',
            'uri' => '/fourth-app/prof/acquiring',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['postUrl'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['code']) || !isset($parseData['message'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['code'] !== '000') {
            throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['payUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 手機支付
        if ($this->options['paymentVendorId'] == 1097) {
            return ['act_url' => $parseData['payUrl']];
        }

        $this->setQrcode($parseData['payUrl']);

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

        $encodeStr = '';

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }

        if (!isset($this->options['hmac'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

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
        $this->verifyPrivateKey();

        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $this->trackingRequestData['hmac'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/fourth-app/queryForMerchant',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->trackingRequestData)),
            'header' => [],
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        // 如果沒有code要丟例外
        if (!isset($parseData['code'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 必要參數為空或格式錯誤
        if ($parseData['code'] === '100100') {
            throw new PaymentConnectionException('Submit the parameter error', 180075, $this->getEntryId());
        }

        // 簽名校驗失敗
        if ($parseData['code'] === '100300') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Merchant sign error',
                180127,
                $this->getEntryId()
            );
        }

        // 商號不存在
        if ($parseData['code'] === '100401') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Merchant is not exist',
                180086,
                $this->getEntryId()
            );
        }

        // 訂單號不存在
        if ($parseData['code'] === '120000') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        // 訂單正在處理中
        if ($parseData['code'] === '120001') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData['code'] !== '000000') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        if (!isset($parseData['data'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 訂單查詢結果驗證
        $this->trackingResultVerify($parseData['data']);

        if (!isset($parseData['data']['hmac'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $encodeStr = '';

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData['data'])) {
                $encodeStr .= $parseData['data'][$paymentKey];
            }
        }

        if ($parseData['data']['hmac'] != hash_hmac('md5', $encodeStr, $this->privateKey)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['data']['merchantOrderno'] != $this->trackingRequestData['merchantOrderNo']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['data']['result'] == 'DEAL') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData['data']['reCode'] != '1' || $parseData['data']['result'] != 'SUCCESS') {
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

        foreach ($this->trackingEncodeParams as $index) {
            $encodeStr .= $this->trackingRequestData[$index];
        }

        return hash_hmac('md5', $encodeStr, $this->privateKey);
    }
}
