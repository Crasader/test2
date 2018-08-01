<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 新商銀信
 */
class NewShangYinXin extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'service' => 'directPay', // 接口名稱，固定值。
        'merchantId' => '', // 商戶號
        'notifyUrl' => '', // 異步通知url
        'returnUrl' => '', // 返回url，可空
        'detailUrl' => '', // 商品url，可空
        'signType' => 'RSA', // 簽名類型
        'inputCharset' => 'UTF-8', // 參數編碼
        'outOrderId' => '', // 訂單號
        'subject' => '', // 商品名稱，不可空
        'body' => 'body', // 商品描述，不可空
        'transAmt' => '', // 交易金額
        'payMethod' => 'bankPay', // 支付方式(bankPay: 網銀直連, default_wechat: 微信)
        'defaultBank' => '', // 網銀
        'channel' => 'B2C', // 銀行渠道。B2C:個人
        'cardAttr' => '01', // 卡類型。01:借記卡
        'ip' => '', // 終端ip
        'sign' => '', // 簽名值
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantId' => 'number',
        'outOrderId' => 'orderId',
        'subject' => 'orderId',
        'transAmt' => 'amount',
        'notifyUrl' => 'notify_url',
        'defaultBank' => 'paymentVendorId',
        'ip' => 'ip',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'service',
        'merchantId',
        'notifyUrl',
        'inputCharset',
        'outOrderId',
        'subject',
        'body',
        'transAmt',
        'payMethod',
        'defaultBank',
        'channel',
        'cardAttr',
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'ICBC', // 中國工商銀行
        2 => 'COMM', // 交通銀行
        3 => 'ABC', // 中國農業銀行
        4 => 'CCB', // 中國建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 中國民生銀行
        8 => 'SPDB', // 上海浦東發展銀行
        9 => 'BJBANK', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'CITIC', // 中信銀行
        12 => 'CEB', // 中國光大銀行
        13 => 'HXBANK', // 華夏銀行
        14 => 'GDB', // 廣東發展銀行
        15 => 'SPABANK', // 平安銀行
        16 => 'PSBC', // 中國郵政
        17 => 'BOC', // 中國銀行
        1090 => '', // 微信_二維
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'notifyId' => 1,
        'notifyTime' => 1,
        'outOrderId' => 1,
        'subject' => 0,
        'body' => 0,
        'transAmt' => 1,
        'tradeStatus' => 1,
        'merchantId' => 1,
        'outAcctId' => 0,
        'buyerId' => 0,
        'localOrderId' => 0,
        'transTime' => 0,
        'inputCharset' => 1,
        'financialId' => 0,
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'merchantId' => '', // 商戶號
        'outOrderId' => '', // 訂單號
        'service' => 'orderQuery', // 接口名稱
        'inputCharset' => 'utf-8', // 參數編碼
        'signType' => 'RSA', // 簽名類型
        'sign' => '', // 簽名數據
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchantId' => 'number',
        'outOrderId' => 'orderId',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'tranAmt' => 1,
        'srcOutOrderId' => 1,
        'payOrderId' => 1,
        'payStatus' => 1,
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
        if (!array_key_exists($this->requestData['defaultBank'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        $this->requestData['defaultBank'] = $this->bankMap[$this->requestData['defaultBank']];
        $this->requestData['transAmt'] = sprintf('%.2f', $this->requestData['transAmt']);

        // 二維支付(微信)
        if ($this->options['paymentVendorId'] == 1090) {
            $this->requestData['payMethod'] = 'default_wechat';

            $removeParams = [
                'returnUrl',
                'detailUrl',
                'defaultBank',
                'channel',
                'cardAttr',
            ];

            foreach ($removeParams as $removeParam) {
                unset($this->requestData[$removeParam]);
            }

            $this->requestData['sign'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/olgateway/scan/scanPay.htm',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:28.0) Gecko/20100101 Firefox/28.0',
                ],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = $this->xmlToArray($result);

            if (!isset($parseData['reCode']) || !isset($parseData['message'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['reCode'] != 'SUCCESS') {
                throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['sign'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            foreach ($parseData as $key => $value) {
                if ($value !== '' && $key != 'sign') {
                    $encodeData[$key] = $value;
                }
            }

            // 針對$encodeData按字母做升序排列
            ksort($encodeData);

            // 依key1=value1&key2=value2&...&keyN=valueN組成字串
            $encodeStr = urldecode(http_build_query($encodeData));

            // 新商銀信回傳簽名有先將字符'+'換成'*'和'/'換成'-'，必須先轉換回來再進行base64_decode()
            $sign = str_replace('*', '+', $parseData['sign']);
            $sign = str_replace('-', '/', $sign);
            $sign = base64_decode($sign);

            if (!openssl_verify($encodeStr, $sign, $this->getRsaPublicKey())) {
                throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
            }

            if (!isset($parseData['payCode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['payCode']);

            return [];
        }

        // 移除網銀不需要的參數
        unset($this->requestData['ip']);

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        return $this->requestData;
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

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            // 如果有key而且不是空值的參數才需要做加密
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) !== '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN組成字串
        $encodeStr = urldecode(http_build_query($encodeData));

        // 新商銀信回傳簽名有先將字符'+'換成'*'和'/'換成'-'，必須先轉換回來再進行base64_decode()
        $sign = str_replace('*', '+', $this->options['sign']);
        $sign = str_replace('-', '/', $sign);
        $sign = base64_decode($sign);

        if (!openssl_verify($encodeStr, $sign, $this->getRsaPublicKey())) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['tradeStatus'] != '2') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['outOrderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['transAmt'] != $entry['amount']) {
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

        // 執行訂單查詢
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'GET',
            'uri' => '/olgateway/orderQuery.htm',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
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
        $this->trackingVerify();

        // 訂單查詢參數設定
        $this->setTrackingRequestData();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/olgateway/orderQuery.htm?' . http_build_query($this->trackingRequestData),
            'method' => 'GET',
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
        // 檢查訂單查詢返回參數
        $parseData = $this->xmlToArray($this->options['content']);

        if (!isset($parseData['pays']['pay'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $this->trackingResultVerify($parseData['pays']['pay']);

        if ($parseData['pays']['pay']['payStatus'] == 'ORDER_STATUS_PENDING') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($parseData['pays']['pay']['payStatus'] != 'ORDER_STATUS_SUC') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['pays']['pay']['srcOutOrderId'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['pays']['pay']['tranAmt'] != $this->options['amount']) {
            throw new PaymentException('Order Amount error', 180058);
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

        $this->trackingRequestData['sign'] = $this->trackingEncode();
    }

    /**
     * 支付時的加密
     *
     * @return string
     */
    protected function encode()
    {
        $encodeData = [];

        /**
         * 組織加密簽名，排除sign(加密簽名)、sign_type(簽名方式)，
         * 其他非空的參數都要納入加密
         */
        foreach ($this->requestData as $key => $value) {
            if ($key != 'sign' && $key != 'signType' && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode(base64_encode($sign));
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeData = [];

        // 組織加密簽名，排除sign和signType，其他非空的參數都要納入加密
        foreach ($this->trackingRequestData as $key => $value) {
            if ($key != 'sign' && $key != 'signType' && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN組成字串
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode(base64_encode(($sign)));
    }
}
