<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * UNIPAY支付
 */
class UniPayZhiFu extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'traceId' => '', // 系統追蹤號，保證唯一即可
        'method' => 'scanpay.order.create', // 接口服務名
        'org' => '', // 接入機構號
        'timestamp' => '', // 請求時間戳，單位：毫秒
        'request' => '', // 業務參數，需用AES加密
        'sign' => '', // 簽名
        'mchId' => '', // 商號
        'prodId' => '', // 產品編號
        'orderId' => '', // 訂單號
        'ipAddress' => '', // IP地址
        'tradeType' => 'ORDER_CODE', // 交易類型，二維:ORDER_CODE
        'payChannel' => '', // 支付渠道
        'totalAmount' => '', // 交易金額，單位:分
        'settlementPeriod' => '', // 結算週期(D0/T1)
        'feeType' => 'CNY', // 貨幣類型
        'title' => '', // 商品標題，不可為空
        'body' => '', // 商品描述，不可為空
        'jumpUrl' => '', // 前端跳轉頁面
        'notifyUrl' => '', // 異步通知地址
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'traceId' => 'orderId',
        'timestamp' => 'orderCreateDate',
        'mchId' => 'number',
        'orderId' => 'orderId',
        'ipAddress' => 'ip',
        'payChannel' => 'paymentVendorId',
        'totalAmount' => 'amount',
        'title' => 'orderId',
        'body' => 'orderId',
        'jumpUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的業務參數
     *
     * @var array
     */
    protected $encodeParams = [
        'mchId',
        'prodId',
        'orderId',
        'ipAddress',
        'tradeType',
        'payChannel',
        'totalAmount',
        'settlementPeriod',
        'feeType',
        'title',
        'body',
        'jumpUrl',
        'notifyUrl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *      0: 可不返回的參數
     *      1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'orgNo' => 1,
        'mchId' => 1,
        'prodId' => 1,
        'tradeId' => 1,
        'orderId' => 1,
        'rootOrderId' => 0,
        'payChannel' => 1,
        'tradeType' => 1,
        'tradeState' => 1,
        'feeType' => 1,
        'totalAmount' => 1,
        'actualPayAmount' => 1,
        'settlementAmount' => 1,
        'advanceFee' => 1,
        'settlementPeriod' => 1,
        'bankCardType' => 0,
        'openId' => 0,
        'subOpenId' => 0,
        'isSubscribe' => 0,
        'subIsSubscribe' => 0,
        'accountId' => 0,
        'payTime' => 0,
        'errorCode' => 0,
        'errorCodeDes' => 0,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1092' => 'ALIPAY', // 支付寶_二維
        '1098' => 'ALIPAY', // 支付寶_手機支付
        '1111' => 'UNION_PAY', // 銀聯_二維
        '1120' => 'UNION_PAY', // 銀聯_手機支付
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
        if (!array_key_exists($this->requestData['payChannel'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $date = new \DateTime($this->requestData['timestamp']);
        $this->requestData['timestamp'] = $date->getTimestamp() * 1000;
        $this->requestData['totalAmount'] = round($this->requestData['totalAmount'] * 100);
        $this->requestData['payChannel'] = $this->bankMap[$this->requestData['payChannel']];

        // 因返回的參數均加密，故需串訂單號
        $this->requestData['notifyUrl'] = sprintf(
            '%s?orderId=%s',
            $this->requestData['notifyUrl'],
            $this->requestData['orderId']
        );

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['org']);
        $this->requestData['org'] = $merchantExtraValues['org'];

        // 手機支付
        if (in_array($this->options['paymentVendorId'], [1098, 1120])) {
            $this->requestData['tradeType'] = 'H5_WAP';
        }

        // 設定各通道產品編號及結算週期參數
        $this->setProductCodeAndSettlePeriod();

        // 業務參數需要AES加密
        $this->requestData['request'] = $this->aesEncrypt();

        // 移除已加密的業務參數
        foreach ($this->encodeParams as $encodeKey) {
            if (array_key_exists($encodeKey, $this->requestData)) {
                unset($this->requestData[$encodeKey]);
            }
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/req',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' =>  json_encode($this->requestData),
            'header' => ['Content-Type' => 'application/json; charset=utf-8'],
        ];

        $result = $this->curlRequest($curlParam);

        $parseData = json_decode($result, true);

        // 先確認通訊成功
        if (!isset($parseData['rstCode'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['rstCode'] !== '000000') {
            if (isset($parseData['rstMsg'])) {
                throw new PaymentConnectionException($parseData['rstMsg'], 180130, $this->getEntryId());
            }

            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        if (!isset($parseData['encryptData'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 其他訊息須先AES解密
        $decrypted = $this->aesDecrypt($parseData['encryptData']);
        $parseData = json_decode($decrypted, true);

        if (!isset($parseData['resultCode'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['resultCode'] !== 'SUCCESS') {
            if (isset($parseData['errorCodeDes'])) {
                throw new PaymentConnectionException($parseData['errorCodeDes'], 180130, $this->getEntryId());
            }

            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        if (!isset($parseData['tradeState'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['tradeState'] !== 'WAIT_PAY') {
            if (isset($parseData['errorCodeDes'])) {
                throw new PaymentConnectionException($parseData['errorCodeDes'], 180130, $this->getEntryId());
            }

            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        if (!isset($parseData['codeUrl']) && !isset($parseData['tradeUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 二維
        if (in_array($this->options['paymentVendorId'], [1092, 1111])) {
            $this->setQrcode($parseData['codeUrl']);

            return [];
        }

        $parsedUrl = $this->parseUrl($parseData['tradeUrl']);

        // Form使用GET才能正常跳轉
        $this->payMethod = 'GET';

        return [
            'post_url' => $parsedUrl['url'],
            'params' => $parsedUrl['params'],
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

        // 返回參數驗證
        if (!isset($this->options['encryptData'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $sign = base64_decode($this->options['sign']);
        if (!openssl_verify($this->options['encryptData'], $sign, $this->getRsaPublicKey())) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 內容需要AES解密
        $decrypt = $this->aesDecrypt($this->options['encryptData']);
        $this->options = json_decode($decrypt, true);

        // 驗證解密後的參數
        $this->payResultVerify();

        if ($this->options['tradeState'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['totalAmount'] != round($entry['amount'] * 100)) {
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

        foreach ($this->requestData as $key => $value) {
            if ($key !== 'sign' && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        ksort($encodeData);
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }

    /**
     * 設定各通道的產品編號以及結算週期
     */
    private function setProductCodeAndSettlePeriod()
    {
        $prodIdMap = [
            1092 => 'AliScanProdId',
            1098 => 'AliPhoneProdId',
            1111 => 'UnionScanProdId',
            1120 => 'UnionPhoneProdId',
        ];

        $settlementPeriodMap = [
            1092 => 'AliScanSettlePeriod',
            1098 => 'AliPhoneSettlePeriod',
            1111 => 'UnionScanSettlePeriod',
            1120 => 'UnionPhoneSettlePeriod',
        ];

        $prodIdExtraKey = $prodIdMap[$this->options['paymentVendorId']];
        $settlementPeriodExtraKey = $settlementPeriodMap[$this->options['paymentVendorId']];

        // 取得商家附加設定值
        $needles = [
            $prodIdExtraKey,
            $settlementPeriodExtraKey,
        ];
        $merchantExtraValues = $this->getMerchantExtraValue($needles);

        $this->requestData['prodId'] = $merchantExtraValues[$prodIdExtraKey];
        $this->requestData['settlementPeriod'] = $merchantExtraValues[$settlementPeriodExtraKey];
    }

    /**
     * AES加密
     *
     * @return string
     */
    private function aesEncrypt()
    {
        $encodeData = [];

        foreach ($this->encodeParams as $index) {
            if (array_key_exists($index, $this->requestData)) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        $encodeStr = openssl_encrypt(json_encode($encodeData), 'aes-128-ecb', $this->privateKey, OPENSSL_RAW_DATA);

        return bin2hex($encodeStr);
    }

    /**
     * AES解密
     *
     * @param string $encodeStr 待解字串
     * @return string
     */
    private function aesDecrypt($encodeStr)
    {
        return openssl_decrypt(hex2bin($encodeStr), 'aes-128-ecb', $this->privateKey, OPENSSL_RAW_DATA);
    }
}
