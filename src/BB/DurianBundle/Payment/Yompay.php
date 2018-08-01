<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 優付
 */
class Yompay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'VERSION' => 'V2.0', // 版本號
        'INPUT_CHARSET' => 'UTF-8', // 參數字符集編碼
        'RETURN_URL' => '', // 同步跳轉通知網址
        'NOTIFY_URL' => '', // 異步通知地址
        'BANK_CODE' => '', // 銀行編碼
        'MER_NO' => '', // 商戶號
        'ORDER_NO' => '', // 訂單號
        'ORDER_AMOUNT' => '', // 金額，以元為單位，精確到小數點後兩位
        'PRODUCT_NAME' => '', // 商品名稱，選填
        'PRODUCT_NUM' => '', // 商品數量，選填
        'REFERER' => '', // 來路域名
        'CUSTOMER_IP' => '', // 消費者IP，選填
        'CUSTOMER_PHONE' => '', // 消費者電話，選填
        'RECEIVE_ADDRESS' => '', // 收貨地址，選填
        'RETURN_PARAMS' => '', // 回傳參數，選填
        'SIGN' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'MER_NO' => 'number',
        'ORDER_NO' => 'orderId',
        'ORDER_AMOUNT' => 'amount',
        'RETURN_URL' => 'notify_url',
        'NOTIFY_URL' => 'notify_url',
        'BANK_CODE' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'MER_NO',
        'VERSION',
        'INPUT_CHARSET',
        'RETURN_URL',
        'NOTIFY_URL',
        'BANK_CODE',
        'ORDER_NO',
        'ORDER_AMOUNT',
        'PRODUCT_NAME',
        'PRODUCT_NUM',
        'REFERER',
        'CUSTOMER_IP',
        'CUSTOMER_PHONE',
        'RECEIVE_ADDRESS',
        'RETURN_PARAMS',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'mer_no' => 1,
        'order_no' => 1,
        'order_amount' => 1,
        'trade_no' => 1,
        'trade_time' => 1,
        'trade_status' => 1,
        'trade_params' => 1,
    ];

    protected $bankMap = [
        '1' => 'ICBC', // 中國工商銀行
        '2' => 'BOCOM', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMBC', // 招商銀行
        '6' => 'CMBCS', // 中國民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'ECITIC', // 中信銀行
        '12' => 'CEBBANK', // 中國光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'CGB', // 廣東發展銀行
        '15' => 'PINGAN', // 平安银行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '1090' => 'WEIXIN', // 微信掃碼
        '1092' => 'ALIPAY', // 支付寶掃碼
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'version' => 'V2.0', // 版本號
        'nonce_str' => '', // 隨機字符串，帶入訂單號
        'mer_no' => '', // 商戶號
        'order_no' => '', // 訂單號
        'trade_date' => '', // 訂單日期，格式YYYYMMDD
        'sign' => '', // 簽名
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'version',
        'nonce_str',
        'mer_no',
        'order_no',
        'trade_date',
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'order_no' => 'orderId',
        'mer_no' => 'number',
        'trade_date' => 'orderCreateDate',
        'nonce_str' => 'orderId',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'orderStatus' => 1,
        'orderNo' => 1,
        'orderAmount' => 1,
        'orderTime' => 1,
        'bankCode' => 1,
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

        $this->options['notify_url'] = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $this->options['notify_url'],
            $this->options['merchantId'],
            $this->options['domain']
        );

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['BANK_CODE'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['ORDER_AMOUNT'] = sprintf('%.2f', $this->requestData['ORDER_AMOUNT']);
        $this->requestData['BANK_CODE'] = $this->bankMap[$this->requestData['BANK_CODE']];
        $referer = parse_url($this->requestData['RETURN_URL']);
        $this->requestData['REFERER'] = $referer['host'];

        // 設定支付平台需要的加密串
        $this->requestData['SIGN'] = $this->encode();

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

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['KEY'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['trade_status'] != 'success') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['order_amount'] != $entry['amount']) {
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

        $date = new \DateTime($this->trackingRequestData['trade_date']);
        $this->trackingRequestData['trade_date'] = $date->format('Ymd');

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/query/',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $data = json_decode($result, true);

        if (!isset($data['status']) || !isset($data['content'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 訂單未支付
        if ($data['status'] == '10005') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 不等於10000即為支付失敗
        if ($data['status'] != '10000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        $this->trackingResultVerify($data['content']);

        if ($data['content']['orderAmount'] != $this->options['amount']) {
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
            $encodeData[$paymentKey] = $this->requestData[$paymentKey];
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['KEY'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
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

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['KEY'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}
