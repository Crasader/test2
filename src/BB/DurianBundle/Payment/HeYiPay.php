<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 合易支付
 */
class HeYiPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'funCode' => '1002', // 業務功能碼，1002:網銀、1001:二維
        'merId' => '', // 商戶號
        'orderDate' => '', // 訂單日期，格式Ymd
        'merOrderId' => '', // 訂單編號
        'orderAmt' => '', // 金額，單位為分，整數
        'goodsId' => '', // 商品編號，非必填
        'goodsName' => '', // 商品名稱，設定username方便業主比對
        'retUrl' => '', // 支付完成後跳轉網址
        'notifyUrl' => '', // 異步通知網址
        'cardType' => '1', // 1:借記卡，固定值
        'channelType' => '1', // 1:PC端，固定值
        'bankSegment' => '', // 銀行代號，網銀用
        'currency' => 'CNY', // 幣種，固定值
        'version' => 'V1.0', // 版本號，固定值
        'userIp' => '', // 用戶IP
        'showUrl' => '', // 商品展示url，非必填
        'merPriv' => '', // 商戶私有域，非必填
        'expand' => '', // 業務擴展信息，非必填
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merId' => 'number',
        'orderDate' => 'orderCreateDate',
        'orderAmt' => 'amount',
        'merOrderId' => 'orderId',
        'goodsName' => 'username',
        'retUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',
        'bankSegment' => 'paymentVendorId',
        'userIp' => 'ip',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'retCode' => 1,
        'retMsg' => 1,
        'merId' => 1,
        'merOrderId' => 1,
        'tradeNo' => 1,
        'payDate' => 1,
        'stlDate' => 1,
        'orderAmt' => 1,
        'cardType' => 0,
        'version' => 1,
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
        'funCode' => '1021', // 業務功能碼
        'merId' => '', // 商戶號
        'orderDate' => '', // 訂單日期，格式Ymd
        'merOrderId' => '', // 訂單編號
        'version' => 'V1.0', // 版本號，固定值
        'sign' => '', // 簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merId' => 'number',
        'orderDate' => 'orderCreateDate',
        'merOrderId' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'funCode',
        'merId',
        'orderDate',
        'merOrderId',
        'version',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'retCode' => 1,
        'retMsg' => 1,
        'merId' => 1,
        'merOrderId' => 1,
        'orderDate' => 1,
        'tradeNo' => 1,
        'payType' => 1,
        'cardType' => 0,
        'status' => 1,
        'orderAmt' => 1,
        'refundAmt' => 1,
        'goodsId' => 0,
        'goodsName' => 1,
        'version' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => '1001', // 中國工商銀行
        2 => '1005', // 交通銀行
        3 => '1002', // 中國農業銀行
        4 => '1004', // 建設銀行
        5 => '1012', // 招商銀行
        6 => '1010', // 中國民生銀行
        8 => '1014', // 上海浦東發展銀行
        9 => '1016', // 北京銀行
        10 => '1013', // 興業銀行
        11 => '1007', // 中信銀行
        12 => '1008', // 中國光大銀行
        13 => '1009', // 華夏銀行
        14 => '1017', // 廣東發展銀行
        15 => '1011', // 平安銀行
        16 => '1006', // 中國郵政儲蓄銀行
        17 => '1003', // 中國銀行
        19 => '1025', // 上海銀行
        234 => '1103', // 北京農村商業銀行
        1092 => 'ALU', // 支付寶_二維
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
        if (!array_key_exists($this->requestData['bankSegment'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bankSegment'] = $this->bankMap[$this->requestData['bankSegment']];
        $date = new \DateTime($this->requestData['orderDate']);
        $this->requestData['orderDate'] = $date->format('Ymd');
        // 金額以分為單位，必須為整數
        $this->requestData['orderAmt'] = round($this->requestData['orderAmt'] * 100);

        // 二維支付(支付寶)
        if ($this->options['paymentVendorId'] == 1092) {
            return $this->getQrcodePayData();
        }

        return $this->getBankPayData();
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            // 如果有key才需要做加密
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有簽名擋也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $sign = base64_decode(rawurldecode(urlencode($this->options['sign'])));

        if (!openssl_verify($encodeStr, $sign, $this->getRsaPublicKey(), OPENSSL_ALGO_SHA1)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['retCode'] != '0000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['merOrderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderAmt'] != round($entry['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 訂單查詢
     */
    public function paymentTracking()
    {
        $this->trackingVerify();

        // 訂單查詢參數設定
        $this->setTrackingRequestData();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/paygate/api',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
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

        // 訂單查詢參數設定
        $this->setTrackingRequestData();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/paygate/api',
            'method' => 'POST',
            'form' => $this->trackingRequestData,
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
        $parseData = json_decode($this->options['content'], true);

        if (!isset($parseData['retCode']) || !isset($parseData['retMsg'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['retCode'] != '0000') {
            throw new PaymentConnectionException($parseData['retMsg'], 180123, $this->getEntryId());
        }

        $this->trackingResultVerify($parseData);

        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $encodeData = [];

        // 組織加密串
        foreach ($parseData as $key => $value) {
            if ($key != 'sign') {
                $encodeData[$key] = trim($value);
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = base64_decode(rawurldecode(urlencode($parseData['sign'])));

        if (!openssl_verify($encodeStr, $sign, $this->getRsaPublicKey(), OPENSSL_ALGO_SHA1)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['status'] == '1') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData['status'] != '2') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['merOrderId'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['orderAmt'] != round($this->options['amount'] * 100)) {
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
            if ($key != 'sign') {
                $encodeData[$key] = $value;
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';

        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey(), OPENSSL_ALGO_SHA1)) {
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

        foreach ($this->trackingEncodeParams as $paymentKey) {
            $encodeData[$paymentKey] = $this->trackingRequestData[$paymentKey];
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';

        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey(), OPENSSL_ALGO_SHA1)) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }

    /**
     * 取得二維支付參數
     *
     * @return array
     */
    private function getQrcodePayData()
    {
        $this->requestData['funCode'] = '1001';
        $this->requestData['payType'] = $this->requestData['bankSegment']; // 支付方式，二維支付必須帶入

        // 二維支付不需要的參數
        $removeParams = [
            'retUrl',
            'cardType',
            'channelType',
            'bankSegment',
            'currency',
            'userIp',
            'showUrl',
            'goodsId',
            'expand',
            'merPriv',
        ];

        // 移除二維支付不需傳遞的參數
        foreach ($removeParams as $removeParam) {
            unset($this->requestData[$removeParam]);
        }

        // 設定支付平台需要的簽名串
        $this->requestData['sign'] = $this->encode();

        // 取得支付對外返回參數
        $parseData = $this->getPayReturnData();

        // 驗證支付對外返回是否成功
        $this->verifyPayReturn($parseData);

        // 驗證支付對外返回簽名
        $this->verifyPayReturnSign($parseData);

        if (!isset($parseData['codeUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $this->setQrcode($parseData['codeUrl']);

        return [];
    }

    /**
     * 取得網銀支付參數
     *
     * @return array
     */
    private function getBankPayData()
    {
        // 設定支付平台需要的簽名串
        $this->requestData['sign'] = $this->encode();

        // 取得支付對外返回參數
        $parseData = $this->getPayReturnData();

        // 驗證支付對外返回是否成功
        $this->verifyPayReturn($parseData);

        // 驗證支付對外返回簽名
        $this->verifyPayReturnSign($parseData);

        if (!isset($parseData['payUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $param = [];

        $parseUrl = parse_url($parseData['payUrl']);

        $parseUrlValues = [
            'scheme' => 1,
            'host' => 1,
            'path' => 1,
            'query' => 1,
        ];

        foreach ($parseUrlValues as $key => $require) {
            if ($require && !isset($parseUrl[$key])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }
        }

        parse_str($parseUrl['query'], $param);

        if (!isset($param['cipher_data'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $postUrl = sprintf(
            '%s://%s%s',
            $parseUrl['scheme'],
            $parseUrl['host'],
            $parseUrl['path']
        );

        return [
            'post_url' => $postUrl,
            'params' => [
                'cipher_data' => $param['cipher_data'],
            ],
        ];
    }

    /**
     * 取得支付對外返回參數
     *
     * @return array
     */
    private function getPayReturnData()
    {
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/paygate/api',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->requestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        return $parseData;
    }

    /**
     * 驗證支付對外返回是否成功
     *
     * @param array $parseData
     */
    private function verifyPayReturn($parseData)
    {
        if (!isset($parseData['retCode']) || !isset($parseData['retMsg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['retCode'] != '0000') {
            throw new PaymentConnectionException($parseData['retMsg'], 180130, $this->getEntryId());
        }
    }

    /**
     * 驗證支付對外返回簽名
     *
     * @param array $parseData
     */
    private function verifyPayReturnSign($parseData)
    {
        if (!isset($parseData['sign'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $encodeData = [];

        // 組織加密串
        foreach ($parseData as $key => $value) {
            if ($key != 'sign') {
                $encodeData[$key] = trim($value);
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = base64_decode(rawurldecode(urlencode($parseData['sign'])));

        if (!openssl_verify($encodeStr, $sign, $this->getRsaPublicKey(), OPENSSL_ALGO_SHA1)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }
    }

    /**
     * 訂單查詢參數設定
     */
    private function setTrackingRequestData()
    {
        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $date = new \DateTime($this->trackingRequestData['orderDate']);
        $this->trackingRequestData['orderDate'] = $date->format('Ymd');

        // 設定查詢時支付平台需要的簽名串
        $this->trackingRequestData['sign'] = $this->trackingEncode();
    }
}
