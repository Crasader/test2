<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 91安付
 */
class AnFu91 extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'Mer_code' => '', // 商號
        'Billno' => '', // 訂單號
        'Amount' => '', // 金額
        'Date' => '', // 訂單日期
        'Currency_Type' => 'RMB', // 幣別
        'Gateway_Type' => '01', // 支付種類(人民幣借記卡)
        'Attach' => '', // 商戶數據包(存 username 方便業主對帳)
        'OrderEncodeType' => '5', // 訂單接口加密方式(MD5)
        'RetEncodeType' => '17', // 交易返回接口加密方式(MD5)
        'Rettype' => '1', // 返回方式
        'ServerUrl' => '', // 伺服器返回url
        'BankCo' => '', // 銀行代碼
        'SignMD5' => '', // 簽名數據
        'Merchanturl' => '', // 前台返回(可為空)
        'Lang' => '', // 語言(可為空)
        'FailUrl' => '', // 支付結果失敗返回(可為空)
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'Mer_code' => 'number',
        'Billno' => 'orderId',
        'Amount' => 'amount',
        'Date' => 'orderCreateDate',
        'Attach' => 'username',
        'ServerUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'Billno',
        'Currency_Type',
        'Amount',
        'Date',
        'OrderEncodeType',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'billno' => 1,
        'Currency_type' => 1,
        'amount' => 1,
        'date' => 1,
        'succ' => 1,
        'ipsbillno' => 1,
        'retencodetype' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '00004', // 中國工商銀行
        '2' => '00005', // 交通銀行
        '3' => '00017', // 中國農業銀行
        '4' => '00012', // 中國建設銀行
        '5' => '00042', // 招商銀行
        '6' => '00013', // 中國民生銀行
        '8' => '00032', // 上海浦東發展銀行
        '9' => '00050', // 北京銀行
        '10' => '00016', // 興業銀行
        '11' => '00092', // 中信銀行
        '12' => '00057', // 中國光大銀行
        '13' => '00041', // 華夏銀行
        '14' => '00052', // 廣東發展銀行
        '15' => '00087', // 深圳平安銀行
        '16' => '00051', // 中國郵政
        '17' => '00083', // 中國銀行
        '19' => '00084', // 上海銀行
        '1090' => 'weixinpay', // 微信
        '1092' => 'alipay', // 支付寶
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'Mer_code' => '', // 商號
        'Billno' => '', // 訂單號
        'SignMD5' => '', // 簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'Mer_code' => 'number',
        'Billno' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'Mer_code',
        'Billno',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'mer_code' => 1,
        'billno' => 1,
        'status' => 1,
        'amount' => 1,
        'date' => 0,
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

        // 檢查銀行是否支援
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 轉成支付平台支援的銀行編碼
        $this->requestData['BankCo'] = $this->bankMap[$this->options['paymentVendorId']];

        // 額外的參數設定
        $date = new \DateTime($this->requestData['Date']);
        $this->requestData['Date'] = $date->format('Ymd');
        $this->requestData['Amount'] = sprintf('%.2f', $this->requestData['Amount']);

        // 設定支付平台需要的加密串
        $this->requestData['SignMD5'] = $this->encode();

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

        // 加密所有 key 都要轉成小寫無底線
        foreach (array_keys($this->decodeParams) as $index) {
            if (array_key_exists($index, $this->options)) {
                $encodeStr .= strtolower(str_replace('_', '', $index)) . $this->options[$index];
            }
        }

        $encodeStr .= $this->privateKey;

        if (!isset($this->options['signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signature'] != strtolower(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['succ'] != 'Y') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['billno'] != $entry['id']) {
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
        $this->trackingRequestData['SignMD5'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/orderquery.aspx',
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
        // 驗證私鑰
        $this->verifyPrivateKey();
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }
        $this->trackingRequestData['SignMD5'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/orderquery.aspx',
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
        $this->verifyPrivateKey();

        // 取得訂單查詢結果
        $result = explode('|', $this->options['content']);

        // 返回不包含任何文件上的格式
        if (count($result) == 1) {
            throw new PaymentConnectionException($result[0], 180123, $this->getEntryId());
        }

        // 失敗返回：fail|失敗原因
        if (count($result) == 2 && $result[0] == 'fail') {
            throw new PaymentConnectionException($result[1], 180123, $this->getEntryId());
        }

        $decodeKeys = ['mer_code', 'billno', 'status', 'amount', 'date', 'signature'];

        // 成功返回：商號|訂單號|狀態|成功金額|成功時間|簽名
        if (count($decodeKeys) !== count($result)) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $parseData = array_combine($decodeKeys, $result);

        $this->trackingResultVerify($parseData);

        // 組織加密串
        $encodeData = [];

        foreach (array_keys($this->trackingDecodeParams) as $key) {
            if (array_key_exists($key, $parseData)) {
                $encodeData[] = $parseData[$key];
            }
        }

        $encodeData[] = $this->privateKey;

        // 檢查簽名參數
        if (!isset($parseData['signature'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 驗證簽名
        if ($parseData['signature'] !== strtolower(md5(implode('|', $encodeData)))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 訂單已退款
        if ($parseData['status'] === 'refund') {
            throw new PaymentConnectionException('Order has been refunded', 180078, $this->getEntryId());
        }

        // 訂單未支付
        if ($parseData['status'] === 'unpaid') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 訂單狀態非成功
        if ($parseData['status'] !== 'paid') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查單號
        if ($parseData['billno'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額
        if ($parseData['amount'] != $this->options['amount']) {
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
        $encodeStr = '';

        // 加密所有 key 都要轉成小寫無底線
        foreach ($this->encodeParams as $index) {
            $encodeStr .= strtolower(str_replace('_', '', $index)) . $this->requestData[$index];
        }

        // 額外的加密設定
        $encodeStr .= $this->privateKey;

        return strtolower(md5($encodeStr));
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
            $encodeData[] = $this->trackingRequestData[$index];
        }

        $encodeData[] = $this->privateKey;

        return strtolower(md5(implode('|', $encodeData)));
    }
}
