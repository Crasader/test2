<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 曼巴支付
 */
class ManBaPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merAccount' => '', // 商戶標識
        'merNo' => '', // 商號
        'orderId' => '', // 訂單號
        'time' => '', // 時間戳
        'amount' => '', // 支付金額，單位:分
        'productType' => '01', // 商品類別碼，固定值
        'product' => '', // 商品名稱，不可空
        'userType' => '1', // 用戶類型，網銀：1
        'payWay' => 'UNIONPAY', // 支付方式，網銀：UNIONPAY
        'payType' => 'GATEWAY_UNIONPAY', // 支付類型，網銀直連：GATEWAY_UNIONPAY
        'userIp' => '', // 客戶端IP
        'returnUrl' => '', // 同步通知網址
        'notifyUrl' => '', // 異步通知網址
        'bankCode' => '', // 銀行編碼
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merNo' => 'number',
        'orderId' => 'orderId',
        'amount' => 'amount',
        'product' => 'orderId',
        'userIp' => 'ip',
        'returnUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',
        'bankCode' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merAccount',
        'merNo',
        'orderId',
        'time',
        'amount',
        'productType',
        'product',
        'userType',
        'payWay',
        'payType',
        'userIp',
        'returnUrl',
        'notifyUrl',
        'bankCode',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merAccount' => 1,
        'mbOrderId' => 1,
        'orderId' => 1,
        'bankCode' => 0,
        'bank' => 0,
        'bankLastNo' => 0,
        'cardType' => 0,
        'amount' => 1,
        'orderStatus' => 1,
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
        1 => 'ICBC', // 中國工商銀行
        3 => 'ABC', // 中國農業銀行
        4 => 'CCB', // 中國建設銀行
        6 => 'CMBC', // 中國民生銀行
        9 => 'BOB', // 北京銀行
        12 => 'CEB', // 光大銀行
        16 => 'PSBC', // 中國郵政儲蓄銀行
        19 => 'SHB', // 上海銀行
        1098 => 'ALIPAY_H5', // 支付寶_手機支付
        1103 => 'SCANPAY_QQ', // QQ_二維
        1104 => 'H5_QQ', // QQ_手機支付
    ];

    /**
     * 曼巴支付支付方式對應的支付方式編號
     *
     * @var array
     */
    private $payWayMap = [
        1098 => 'ALIPAY', // 支付寶_手機支付
        1103 => 'QQPAY', // QQ_二維
        1104 => 'QQPAY', // QQ_手機支付
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
        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];
        $this->requestData['time'] = time();
        $this->requestData['amount'] = round($this->requestData['amount'] * 100);

        // 商家額外的參數設定
        $names = ['merAccount'];
        $extra = $this->getMerchantExtraValue($names);
        $this->requestData['merAccount'] = $extra['merAccount'];

        // 網銀 uri
        $uri = '/paygateway/mbgateway/gatewayorder/v1';

        // 調整二維支付、手機支付 uri 及提交參數
        if (in_array($this->options['paymentVendorId'], [1098, 1103, 1104])) {
            $uri = '/paygateway/mbpay/order/v1';

            $this->requestData['userType'] = '0';
            $this->requestData['payWay'] = $this->payWayMap[$this->options['paymentVendorId']];;
            $this->requestData['payType'] = $this->requestData['bankCode'];

            unset($this->requestData['bankCode']);
        }

        // 設定加密簽名
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $requestData = [
            'merAccount' => $extra['merAccount'],
            'data' => $this->encryptData(),
        ];

        $curlParam = [
            'method' => 'POST',
            'uri' => $uri,
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($requestData),
            'header' => [],
        ];
        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['code']) || !isset($parseData['msg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['code'] !== '000000') {
            throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
        }

        // 二維支付
        if ($this->options['paymentVendorId'] == 1103) {
            if (!isset($parseData['data']['qrCode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['data']['qrCode']);

            return [];
        }

        if (!isset($parseData['data']['payUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $urlData = $this->parseUrl($parseData['data']['payUrl']);

        // Form使用GET才能正常跳轉
        $this->payMethod = 'GET';

        return [
            'post_url' => $urlData['url'],
            'params' => $urlData['params'],
        ];
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $this->verifyPrivateKey();

        if (!isset($this->options['data'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $this->setOptions($this->decryptData($this->options['data']));

        $this->payResultVerify();

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = implode($encodeData) . $this->privateKey;

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != sha1($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['orderStatus'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != $entry['amount']) {
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

        foreach ($this->encodeParams as $paymentKey) {
            if (array_key_exists($paymentKey, $this->requestData)) {
                $encodeData[$paymentKey] = $this->requestData[$paymentKey];
            }
        }

        ksort($encodeData);

        return sha1(implode($encodeData) . $this->privateKey);
    }

    /**
     * 加密提交參數
     *
     * @return string
     */
    private function encryptData()
    {
        $encodeData = [];

        // 所有提交的參數都需要加密
        foreach ($this->requestData as $paymentKey => $paymentValue) {
            $encodeData[$paymentKey] = $paymentValue;
        }

        ksort($encodeData);

        $encodeStr = json_encode($encodeData);

        return base64_encode(openssl_encrypt($encodeStr, 'aes-256-ecb', $this->privateKey, OPENSSL_RAW_DATA));
    }

    /**
     * 解密回調參數
     *
     * @param string $data 支付平台回調參數
     * @return array
     */
    protected function decryptData($data)
    {
        $jsonData = openssl_decrypt(base64_decode($data), 'aes-256-ecb', $this->privateKey, OPENSSL_RAW_DATA);

        return json_decode($jsonData, true);
    }
}
