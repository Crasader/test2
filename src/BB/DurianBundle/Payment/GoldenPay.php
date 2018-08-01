<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 金付卡
 */
class GoldenPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'sign' => '', // 簽名
        'merId' => '', // 商戶號
        'version' => '1.0.9', // 版本號
        'terId' => '', // 終端號
        'businessOrdid' => '', // 商戶訂單號
        'orderName' => '', // 訂單名稱
        'tradeMoney' => '', // 訂單金額，單位為分
        'selfParam' => '', // 自定義參數，可空
        'payType' => '', // 支付方式
        'appSence' => '1001', // 應用場景，1001-PC 1002-H5
        'syncURL' => '', // 同步通知地址，可空
        'asynURL' => '', // 異步通知地址
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merId' => 'number',
        'businessOrdid' => 'orderId',
        'orderName' => 'username',
        'tradeMoney' => 'amount',
        'asynURL' => 'notify_url',
        'payType' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merId',
        'businessOrdid',
        'tradeMoney',
        'selfParam',
        'orderName',
        'syncURL',
        'terId',
        'payType',
        'appSence',
        'asynURL',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'sign' => 1,
        'merId' => 1,
        'version' => 1,
        'encParam' => 1,
    ];

    /**
     * 支付解密驗證時需要加密的業務參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeEncParam = [
        'orderId' => 1,
        'payOrderId' => 1,
        'order_state' => 1,
        'money' => 1,
        'payReturnTime' => 1,
        'selfParam' => 0,
        'payType' => 1,
        'payTypeDesc' => 1,
        'notifyType' => 1,
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
        1102 => '1003', // 網銀(收銀台)
        1090 => '1005', // 微信_二維
        1092 => '1006', // 支付寶_二維
        1098 => '1008', // 支付寶_手機支付
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'version' => '1.0.9', // 版本號
        'merId' => '', // 商戶號
        'sign' => '', // 簽名
        'businessOrdid' => '', // 商戶訂單號
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merId' => 'number',
        'businessOrdid' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'businessOrdid',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'respCode' => 1,
        'orderId' => 1,
        'payOrderId' => 1,
        'order_state' => 1,
        'money' => 1,
        'payReturnTime' => 1,
        'selfParam' => 0,
        'payType' => 1,
        'payTypeDesc' => 1,
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

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['payType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 支付銀行若為手機支付需調整應用場景
        if ($this->options['paymentVendorId'] == 1098) {
            $this->requestData['appSence'] = '1002';
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['terId']);

        // 商家額外的參數設定
        $this->requestData['terId'] = $merchantExtraValues['terId'];
        $this->requestData['payType'] = $this->bankMap[$this->requestData['payType']];
        $this->requestData['tradeMoney'] = round($this->requestData['tradeMoney'] * 100);

        // 支付時業務參數加密
        $this->requestData['encParam'] = $this->encParamEncode();

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        $requestData = [
            'version' => $this->requestData['version'],
            'merId' => $this->requestData['merId'],
            'sign' => $this->requestData['sign'],
            'encParam' => base64_encode($this->requestData['encParam']),
        ];

        return $requestData;
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $this->payResultVerify();

        $encParam = base64_decode($this->options['encParam']);
        $sign = base64_decode($this->options['sign']);

        if (openssl_verify($encParam, $sign, $this->getRsaPublicKey()) !== 1) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 解密業務參數
        $decryptData = $this->decryptEncParam($encParam);

        // 支付結果業務參數結果驗證
        foreach ($this->decodeEncParam as $paymentKey => $require) {
            if ($require && !array_key_exists($paymentKey, $decryptData)) {
                throw new PaymentException('No return parameter specified', 180137);
            }
        }

        if ($decryptData['order_state'] != '1003') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($decryptData['orderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($decryptData['money'] != round($entry['amount'] * 100)) {
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

        // 訂單查詢時業務參數加密
        $this->trackingRequestData['encParam'] = $this->trackingEncParamEncode();

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        // 執行訂單查詢
        if (trim($this->options['verify_url']) === '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $trackingRequestData = [
            'version' => $this->trackingRequestData['version'],
            'merId' => $this->trackingRequestData['merId'],
            'sign' => $this->trackingRequestData['sign'],
            'encParam' => base64_encode($this->trackingRequestData['encParam']),
        ];

        $curlParam = [
            'method' => 'POST',
            'uri' => '/gateway/queryPaymentRecord',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($trackingRequestData),
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
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 訂單查詢時業務參數加密
        $this->trackingRequestData['encParam'] = $this->trackingEncParamEncode();

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        // 執行訂單查詢
        if (trim($this->options['verify_url']) === '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $trackingRequestData = [
            'version' => $this->trackingRequestData['version'],
            'merId' => $this->trackingRequestData['merId'],
            'sign' => $this->trackingRequestData['sign'],
            'encParam' => base64_encode($this->trackingRequestData['encParam']),
        ];

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/gateway/queryPaymentRecord',
            'method' => 'POST',
            'form' => $trackingRequestData,
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
        $data = json_decode($this->options['content'], true);

        if (!isset($data['encParam']) || !isset($data['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $encParam = base64_decode($data['encParam']);
        $sign = base64_decode($data['sign']);

        if (openssl_verify($encParam, $sign, $this->getRsaPublicKey()) !== 1) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 解密業務參數
        $decryptData = $this->decryptEncParam($encParam);

        // respCode為1000時，訂單狀態(order_state)才有效
        if (isset($decryptData['respCode']) && $decryptData['respCode'] != '1000' && isset($decryptData['respDesc'])) {
            throw new PaymentConnectionException($decryptData['respDesc'], 180123, $this->getEntryId());
        }

        if (isset($decryptData['respCode']) && $decryptData['respCode'] != '1000') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        $this->trackingResultVerify($decryptData);

        if ($decryptData['order_state'] != '1003') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($decryptData['orderId'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($decryptData['money'] != round($this->options['amount'] * 100)) {
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
        $sign = '';

        if (!openssl_sign($this->requestData['encParam'], $sign, $this->getRsaPrivateKey())) {
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
        $sign = '';

        if (!openssl_sign($this->trackingRequestData['encParam'], $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }

    /**
     * 支付時業務參數加密
     *
     * @return string
     */
    private function encParamEncode()
    {
        $encodeData = [];

        foreach ($this->encodeParams as $key) {
            $encodeData[$key] = $this->requestData[$key];
        }

        $json = json_encode($encodeData, JSON_UNESCAPED_UNICODE);

        $strSplit = str_split($json, 64);

        $encParam = '';
        foreach ($strSplit as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $this->getRsaPublicKey());
            $encParam .= $data;
        }

        return $encParam;
    }

    /**
     * 訂單查詢時業務參數加密
     *
     * @return string
     */
    private function trackingEncParamEncode()
    {
        $encodeData = [];

        foreach ($this->trackingEncodeParams as $key) {
            $encodeData[$key] = $this->trackingRequestData[$key];
        }

        $json = json_encode($encodeData, JSON_UNESCAPED_UNICODE);

        $strSplit = str_split($json, 64);

        $encParam = '';
        foreach ($strSplit as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $this->getRsaPublicKey());
            $encParam .= $data;
        }

        return $encParam;
    }

    /**
     * 解密業務參數
     *
     * @param string $encParam
     * @return array
     */
    private function decryptEncParam($encParam)
    {
        $strSplit = str_split($encParam, 128);

        $str = '';
        foreach ($strSplit as $part) {
            $temp = '';
            openssl_private_decrypt($part, $temp, $this->getRsaPrivateKey());
            $str .= $temp;
        }

        return json_decode($str, true);
    }
}
