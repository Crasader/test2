<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 融合支付
 */
class UnionPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => '1.0.0', // 版本
        'encoding' => 'UTF-8', // 編碼
        'signature' => '', // 簽名
        'mchId' => '', // 商號
        'cmpAppId' => '', // 應用 ID
        'payTypeCode' => 'web.pay', // 支付渠道編碼
        'outTradeNo' => '', // 訂單號
        'tradeTime' => '', // 交易發送時間
        'amount' => '', // 交易金額(單位：分)
        'summary' => '', // 摘要(存 username 方便業主對帳)
        'deviceIp' => '', // 終端設備 IP
        'returnUrl' => '', // 前台返回(網銀必要參數)
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'mchId' => 'number',
        'outTradeNo' => 'orderId',
        'tradeTime' => 'orderCreateDate',
        'amount' => 'amount',
        'summary' => 'username',
        'deviceIp' => 'ip',
        'returnUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'encoding',
        'mchId',
        'cmpAppId',
        'payTypeCode',
        'outTradeNo',
        'tradeTime',
        'amount',
        'summary',
        'deviceIp',
        'returnUrl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'version' => 1,
        'encoding' => 1,
        'mchId' => 1,
        'amount' => 1,
        'outTradeNo' => 1,
        'payTypeOrderNo' => 1,
        'orderNo' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => '', // 微信(收銀台)
        '1092' => '', // 支付寶(收銀台)
        '1102' => '', // 網銀收銀台
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'version' => '1.0.0', // 版本號
        'encoding' => 'UTF-8', // 編碼
        'mchId' => '', // 商號
        'cmpAppId' => '', // 應用 ID
        'payTypeCode' => 'web.pay', // 支付渠道編碼
        'outTradeNo' => '', // 訂單號
        'signature' => '', // 簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'mchId' => 'number',
        'outTradeNo' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'version',
        'encoding',
        'mchId',
        'cmpAppId',
        'payTypeCode',
        'outTradeNo',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'version' => 1,
        'encoding' => 1,
        'mchId' => 1,
        'cmpAppId' => 1,
        'payTypeTradeNo' => 1,
        'respCode' => 1,
        'respMsg' => 1,
        'outTradeNo' => 1,
        'tradeStatus' => 1,
        'amount' => 0,
        'appPmtChnlId' => 0,
        'tradeNo' => 0,
        'tradeTime' => 0,
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行是否支援
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $merchantExtra = $this->getMerchantExtraValue(['cmpAppId']);
        $this->requestData['cmpAppId'] = $merchantExtra['cmpAppId'];
        $this->requestData['amount'] = round($this->requestData['amount'] * 100);
        $tradeTime = new \Datetime($this->options['orderCreateDate']);
        $this->requestData['tradeTime'] = $tradeTime->format('YmdHis');

        // 產生加密字串
        $this->requestData['signature'] = $this->encode();

        $curlParam = [
            'method' => 'POST',
            'uri' => '/pre.lepay.api/order/add',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->requestData),
            'header' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        ];

        $result = json_decode($this->curlRequest($curlParam), true);

        // 組織加密串
        $encodeData = $result;
        unset($encodeData['signature']);
        ksort($encodeData);
        $encodeStr = sha1(urldecode(http_build_query($encodeData)));

        // 檢查簽名
        if (!isset($result['signature'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $signVerify = openssl_verify($encodeStr, base64_decode($result['signature']), $this->getRsaPublicKey());

        if ($signVerify !== 1) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 檢查是否有返回碼及訊息
        if (!isset($result['respCode']) || !isset($result['respMsg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 返回非成功時直接顯示支付平台回傳訊息
        if ($result['respCode'] != '000000') {
            throw new PaymentConnectionException($result['respMsg'], 180130, $this->getEntryId());
        }

        // 檢查是否有支付平台提交的網址
        if (!isset($result['webOrderInfo'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        return ['act_url' => $result['webOrderInfo']];
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $this->payResultVerify();

        // 驗簽
        $encodeData = [];
        foreach (array_keys($this->decodeParams) as $key) {
            if (array_key_exists($key, $this->options)) {
                $encodeData[$key] = $this->options[$key];
            }
        }
        ksort($encodeData);
        $encodeStr = sha1(urldecode(http_build_query($encodeData)));

        // 檢查簽名
        if (!isset($this->options['signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $signVerify = openssl_verify($encodeStr, base64_decode($this->options['signature']), $this->getRsaPublicKey());

        if ($signVerify !== 1) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 檢查單號
        if ($this->options['outTradeNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額
        if ($this->options['amount'] != round($entry['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 訂單查詢
     */
    public function paymentTracking()
    {
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 商家額外的參數設定
        $merchantExtra = $this->getMerchantExtraValue(['cmpAppId']);
        $this->trackingRequestData['cmpAppId'] = $merchantExtra['cmpAppId'];

        $this->trackingRequestData['signature'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/pre.lepay.api/order/query',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        // 檢查返回碼及訊息
        if (!isset($parseData['respCode']) || !isset($parseData['respMsg'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['respCode'] != '000000') {
            throw new PaymentConnectionException($parseData['respMsg'], 180123, $this->getEntryId());
        }

        // 檢查必要回傳參數
        $this->trackingResultVerify($parseData);

        if (!isset($parseData['signature'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 驗證加密字串是否相符(除了 signature 以外所有回傳都需要加密)
        $encodeData = [];
        foreach (array_keys($this->trackingDecodeParams) as $key) {
            if (array_key_exists($key, $parseData)) {
                $encodeData[$key] = $parseData[$key];
            }
        }
        ksort($encodeData);
        $encodeStr = sha1(urldecode(http_build_query($encodeData)));
        $signVerify = openssl_verify($encodeStr, base64_decode($parseData['signature']), $this->getRsaPublicKey());

        if ($signVerify !== 1) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 交易狀態不為 2 則代表支付失敗
        if ($parseData['tradeStatus'] != 2) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查單號
        if ($parseData['outTradeNo'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額(已支付才會回傳金額)
        if (!isset($parseData['amount'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['amount'] != round($this->options['amount'] * 100)) {
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
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 商家額外的參數設定
        $merchantExtra = $this->getMerchantExtraValue(['cmpAppId']);
        $this->trackingRequestData['cmpAppId'] = $merchantExtra['cmpAppId'];

        $this->trackingRequestData['signature'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/pre.lepay.api/order/query',
            'method' => 'POST',
            'form' => $this->trackingRequestData,
            'headers' => [
                'Host' => $this->options['verify_url'],
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ];

        return $curlParam;
    }

    /**
     * 驗證訂單查詢是否成功
     */
    public function paymentTrackingVerify()
    {
        // 取得訂單查詢結果
        $parseData = json_decode($this->options['content'], true);

        // 檢查返回碼及訊息
        if (!isset($parseData['respCode']) || !isset($parseData['respMsg'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['respCode'] != '000000') {
            throw new PaymentConnectionException($parseData['respMsg'], 180123, $this->getEntryId());
        }

        // 檢查必要回傳參數
        $this->trackingResultVerify($parseData);

        if (!isset($parseData['signature'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 驗證加密字串是否相符(除了 signature 以外所有回傳都需要加密)
        $encodeData = [];
        foreach (array_keys($this->trackingDecodeParams) as $key) {
            if (array_key_exists($key, $parseData)) {
                $encodeData[$key] = $parseData[$key];
            }
        }
        ksort($encodeData);
        $encodeStr = sha1(urldecode(http_build_query($encodeData)));
        $signVerify = openssl_verify($encodeStr, base64_decode($parseData['signature']), $this->getRsaPublicKey());

        if ($signVerify !== 1) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 交易狀態不為 2 則代表支付失敗
        if ($parseData['tradeStatus'] != 2) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查單號
        if ($parseData['outTradeNo'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額(已支付才會回傳金額)
        if (!isset($parseData['amount'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
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
        $encodeData = [];
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);
        $encodeStr = sha1(urldecode(http_build_query($encodeData)));

        $sign = '';

        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeData = [];
        foreach ($this->trackingEncodeParams as $index) {
            $encodeData[$index] = $this->trackingRequestData[$index];
        }

        ksort($encodeData);
        $encodeStr = sha1(urldecode(http_build_query($encodeData)));

        $sign = '';

        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }

    /**
     * 回傳私鑰
     *
     * @return resource
     */
    public function getRsaPrivateKey()
    {
        $this->verifyPrivateKey();

        $content = base64_decode($this->options['rsa_private_key']);

        if (!$content) {
            throw new PaymentException('Rsa private key is empty', 180092);
        }

        $privateCert = [];

        if (!openssl_pkcs12_read($content, $privateCert, $this->privateKey)) {
            throw new PaymentException('Get rsa private key failure', 180093);
        }

        return $privateCert['pkey'];
    }
}
