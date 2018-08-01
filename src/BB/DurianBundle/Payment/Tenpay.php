<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 財付通支付
 *
 * 支付驗證：
 * 1. 驗證不可為空的參數
 * 2. 設定參數
 * 3. 額外處理的參數
 * 4. 設定encodeStr(加密後的字串)
 *
 * 解密驗證：
 * 1. 驗證key
 * 2. 設定參數
 * 3. 驗證結果是否相符
 */
class Tenpay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'cmdno' => '1', //任務代碼。1：支付
        'date' => '', //訂單日期(Ymd)
        'bank_type' => '0', //銀行類型。0：財付通通用支付頁面
        'desc' => '', //商品名稱，存username方便業主對帳
        'purchaser_id' => '', //財付通帳戶
        'bargainor_id' => '', //商號
        'transaction_id' => '', //交易號。共28碼，格式為商號+日期(Ymd)+10碼，預設為商號+訂單號
        'sp_billno' => '', //訂單號
        'total_fee' => '', //訂單金額，以分為單位。
        'fee_type' => '1', //幣別。1：人民幣
        'return_url' => '', //交易返回網址
        'attach' => '1', //商戶數據包，照原樣返回。
        'spbill_create_ip' => '', //用戶IP
        'sign' => '', //加密串
        'cs' => 'utf-8' //編碼
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'bargainor_id' => 'number',
        'sp_billno' => 'orderId',
        'total_fee' => 'amount',
        'return_url' => 'notify_url',
        'desc' => 'username',
        'date' => 'orderCreateDate',
        'spbill_create_ip' => 'ip'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'cmdno',
        'date',
        'bargainor_id',
        'transaction_id',
        'sp_billno',
        'total_fee',
        'fee_type',
        'return_url',
        'attach',
        'spbill_create_ip'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'cmdno' => 1,
        'pay_result' => 1,
        'date' => 1,
        'transaction_id' => 1,
        'sp_billno' => 1,
        'total_fee' => 1,
        'fee_type' => 1,
        'attach' => 1
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '<meta name="TENCENT_ONLINE_PAYMENT" content="China TENCENT">';

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'cmdno' => '2',
        'date' => '',
        'bargainor_id' => '',
        'transaction_id' => '',
        'sp_billno' => '',
        'attach' => '1',
        'output_xml' => '1',
        'charset' => 'UTF-8',
        'sign' => ''
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'bargainor_id' => 'number',
        'sp_billno' => 'orderId',
        'date' => 'orderCreateDate'
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'attach',
        'bargainor_id',
        'charset',
        'cmdno',
        'date',
        'output_xml',
        'sp_billno',
        'transaction_id'
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'attach' => 1,
        'bargainor_id' => 1,
        'cmdno' => 1,
        'date' => 1,
        'fee_type' => 1,
        'pay_info' => 1,
        'pay_result' => 1,
        'sp_billno' => 1,
        'total_fee' => 1,
        'transaction_id' => 1
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

        //額外的參數設定(要送去支付平台的參數)
        $date = new \DateTime($this->requestData['date']);
        $this->requestData['date'] = $date->format('Ymd');
        $this->requestData['total_fee'] = round($this->requestData['total_fee'] * 100);
        $this->requestData['transaction_id'] = $this->requestData['bargainor_id'] . $this->requestData['sp_billno'];

         //設定支付平台需要的加密串
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

        //組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        //沒有Md5Sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['pay_result'] != '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['sp_billno'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_fee'] != round($entry['amount'] * 100)) {
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

        $transactionId = sprintf(
            '%s%s',
            $this->trackingRequestData['bargainor_id'],
            $this->trackingRequestData['sp_billno']
        );
        $this->trackingRequestData['transaction_id'] = $transactionId;
        $date = new \DateTime($this->trackingRequestData['date']);
        $this->trackingRequestData['date'] = $date->format('Ymd');
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/cgi-bin/cfbi_query_order_v3.cgi',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = $this->parseData($result);

        if (!isset($parseData['pay_result'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['pay_result'] != '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }
        $this->trackingResultVerify($parseData);

        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['total_fee'] != round($this->options['amount'] * 100)) {
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
        // 驗證私鑰
        $this->verifyPrivateKey();

        // 驗證訂單查詢參數
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $transactionId = sprintf(
            '%s%s',
            $this->trackingRequestData['bargainor_id'],
            $this->trackingRequestData['sp_billno']
        );
        $this->trackingRequestData['transaction_id'] = $transactionId;
        $date = new \DateTime($this->trackingRequestData['date']);
        $this->trackingRequestData['date'] = $date->format('Ymd');
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/cgi-bin/cfbi_query_order_v3.cgi',
            'method' => 'POST',
            'form' => $this->trackingRequestData,
            'headers' => [
                'Host' => $this->options['verify_url']
            ]
        ];

        return $curlParam;
    }

    /**
     * 驗證訂單查詢是否成功
     */
    public function paymentTrackingVerify()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        // 檢查訂單查詢返回參數
        $parseData = $this->parseData($this->options['content']);

        if (!isset($parseData['pay_result'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['pay_result'] != '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }
        $this->trackingResultVerify($parseData);

        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['total_fee'] != round($this->options['amount'] * 100)) {
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

        //加密設定
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        $encodeData['key'] = $this->privateKey;

        return md5(urldecode(http_build_query($encodeData)));
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeData = [];

        //加密設定
        foreach ($this->trackingEncodeParams as $index) {
            $encodeData[$index] = $this->trackingRequestData[$index];
        }

        $encodeData['key'] = $this->privateKey;

        return md5(http_build_query($encodeData));
    }

    /**
     * 分析支付平台回傳的入款查詢參數
     *
     * @param string $content xml的回傳格式
     * @return array
     */
    public function parseData($content)
    {
        return $this->xmlToArray($content);
    }
}
