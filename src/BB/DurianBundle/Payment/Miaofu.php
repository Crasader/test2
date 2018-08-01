<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 秒付
 */
class Miaofu extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'input_charset' => 'UTF-8', // 參數字符集編碼
        'notify_url' => '', // 服務器異步通知地址
        'return_url' => '', // 頁面同步跳轉通知地址
        'pay_type' => '1', // 支付方式，固定值
        'bank_code' => '', // 銀行編碼
        'merchant_code' => '', // 商戶號
        'order_no' => '', // 商戶訂單號
        'order_amount' => '', // 商戶訂單號金額
        'order_time' => '', // 商戶訂單時間
        'product_name' => '', // 商品名稱。可空
        'product_num' => '', // 商品數量。可空
        'req_referer' => '', // 來路域名
        'customer_ip' => '', // 消費者ip
        'customer_phone' => '', // 消費者電話。可空
        'receive_address' => '', // 收貨地址。可空
        'return_params' => '', // 回傳參數。可空
        'sign' => '', // 簽名
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'notify_url' => 'notify_url',
        'bank_code' => 'paymentVendorId',
        'merchant_code' => 'number',
        'order_no' => 'orderId',
        'order_amount' => 'amount',
        'order_time' => 'orderCreateDate',
        'customer_ip' => 'ip',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'input_charset',
        'notify_url',
        'return_url',
        'pay_type',
        'bank_code',
        'merchant_code',
        'order_no',
        'order_amount',
        'order_time',
        'product_name',
        'product_num',
        'req_referer',
        'customer_ip',
        'customer_phone',
        'receive_address',
        'return_params',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchant_code' => 1,
        'notify_type' => 1,
        'order_no' => 1,
        'order_amount' => 1,
        'order_time' => 1,
        'return_params' => 1,
        'trade_no' => 1,
        'trade_time' => 1,
        'trade_status' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'ICBC', // 中國工商銀行
        2 => 'BOCOM', // 交通銀行
        3 => 'ABC', // 中國農業銀行
        4 => 'CCB', // 中國建設銀行
        5 => 'CMBC', // 招商銀行
        6 => 'CMBCS', // 中國民生銀行
        8 => 'SPDB', // 上海浦東發展銀行
        9 => 'BCCB', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'ECITIC', // 中信銀行
        12 => 'CEBBANK', // 光大銀行
        13 => 'HXB', // 華夏銀行
        14 => 'CGB', // 廣東發展銀行
        15 => 'PINGAN', // 平安銀行
        16 => 'PSBC', // 中國郵政儲蓄銀行
        17 => 'BOC', // 中國銀行
        19 => 'BOS', // 上海銀行
        228 => 'SRCB', // 上海農商銀行
        234 => 'BRCB', // 北京農商銀行
        1090 => 'WEIXIN', // 微信支付
        1092 => 'ZHIFUBAO', // 支付寶
        1097 => 'WEIXIN_H5', // 微信_手機支付
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'input_charset' => 'UTF-8',
        'merchant_code' => '',
        'sign' => '',
        'order_no' => '',
        'trade_no' => '',
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchant_code' => 'number',
        'order_no' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'input_charset',
        'merchant_code',
        'order_no',
        'trade_no',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'is_success' => 1,
        'errror_msg' => 0,
        'merchant_code' => 1,
        'order_no' => 1,
        'order_amount' => 1,
        'order_time' => 1,
        'trade_no' => 1,
        'trade_time' => 1,
        'trade_status' => 1,
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

        // 驗證支付參數
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

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['bank_code'], $this->bankMap)) {
            throw new PaymentException(
                'PaymentVendor is not supported by PaymentGateway',
                180066
            );
        }

        // 額外的參數設定
        $this->requestData['bank_code'] = $this->bankMap[$this->requestData['bank_code']];
        $this->requestData['order_amount'] = sprintf('%.2f', $this->requestData['order_amount']);
        $createAt = new \Datetime($this->requestData['order_time']);
        $this->requestData['order_time'] = $createAt->format('Y-m-d H:i:s');
        $reqReferer = parse_url($this->requestData['notify_url']);
        $this->requestData['req_referer'] = $reqReferer['host'];

        // 設定加密簽名
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
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->payResultVerify();

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['trade_status'] != 'success') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_no'] != $entry['id']) {
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

        // 驗證訂單查詢參數
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        // 執行訂單查詢
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/query',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);

        // 檢查訂單查詢返回參數
        $parseData = $this->xmlToArray($result);

        // 沒有response就要丟例外
        if (!isset($parseData['response'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 訂單不存在
        $message = '/参数order_no的值(\d+)不存在/';

        if (isset($parseData['response']['error_msg']) && preg_match($message, $parseData['response']['error_msg'])) {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        $this->trackingResultVerify($parseData['response']);

        // 訂單查詢異常
        if ($parseData['response']['is_success'] != 'TRUE') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData['response']) && $parseData['response'][$paymentKey] !== '') {
                $encodeData[$paymentKey] = $parseData['response'][$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($parseData['response']['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['response']['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['response']['trade_status'] == 'paying') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData['response']['trade_status'] != 'success') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['response']['order_amount'] != $this->options['amount']) {
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
            if (isset($this->requestData[$paymentKey]) && $this->requestData[$paymentKey] !== '') {
                $encodeData[$paymentKey] = $this->requestData[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
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
            if (isset($this->trackingRequestData[$paymentKey]) && $this->trackingRequestData[$paymentKey] !== '') {
                $encodeData[$paymentKey] = $this->trackingRequestData[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}