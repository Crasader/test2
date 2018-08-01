<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 澤聖支付
 */
class ZsagePay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantCode' => '', // 商戶號
        'outOrderId' => '', // 訂單號
        'totalAmount' => '', // 支付金額，單位分，需整數
        'goodsName' => '', // 產品名稱，非必填
        'goodsExplain' => '', // 產品描述，非必填
        'orderCreateTime' => '', // 訂單成立時間
        'lastPayTime' => '', // 訂單逾期時間，非必填
        'merUrl' => '', // 商户取貨URL
        'noticeUrl' => '', // 異步通知網址
        'bankCode' => '', // 支付銀行代碼
        'bankCardType' => '01', // 支付銀行卡類型，固定值
        'merchantChannel' => '', // 商戶渠道代碼，非必填
        'ext' => '', // 擴展字段，非必填
        'sign' => '', // 簽名
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantCode' => 'number',
        'outOrderId' => 'orderId',
        'totalAmount' => 'amount',
        'orderCreateTime' => 'orderCreateDate',
        'merUrl' => 'notify_url',
        'noticeUrl' => 'notify_url',
        'bankCode' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantCode',
        'outOrderId',
        'totalAmount',
        'orderCreateTime',
        'lastPayTime',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantCode' => 1,
        'instructCode' => 1,
        'transType' => 1,
        'outOrderId' => 1,
        'transTime' => 1,
        'totalAmount' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = "{'code':'00'}";

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'merchantCode' => '', // 商戶號
        'outOrderId' => '', // 訂單號
        'sign' => '', // 簽名
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'merchantCode',
        'outOrderId',
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchantCode' => 'number',
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
        'merchantCode' => 1,
        'outOrderId' => 1,
        'amount' => 1,
        'replyCode' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 工商銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMB', // 招商銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CITIC', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣發銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
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

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        $this->requestData['totalAmount'] = round($this->requestData['totalAmount'] * 100);

        $date = new \DateTime($this->requestData['orderCreateTime']);
        $this->requestData['orderCreateTime'] = $date->format('YmdHis');
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];

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
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->payResultVerify();

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['KEY'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有返回sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strtoupper($this->options['sign']) != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['outOrderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['totalAmount'] != round($entry['amount'] * 100)) {
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

        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/ebank/queryOrder.do',
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
        // 驗證私鑰
        $this->verifyPrivateKey();

        // 驗證訂單查詢參數
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/ebank/queryOrder.do',
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
        // 驗證私鑰
        $this->verifyPrivateKey();

        $parseData = json_decode($this->options['content'], true);

        if (!isset($parseData['code']) || !isset($parseData['msg'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['code'] !== '00') {
            throw new PaymentConnectionException($parseData['msg'], 180123, $this->getEntryId());
        }

        if (!isset($parseData['data'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $data = $parseData['data'];

        $this->trackingResultVerify($data);

        $encodeData = [];

        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $data)) {
                $encodeData[$paymentKey] = $data[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['KEY'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($data['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (strtoupper($data['sign']) != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($data['replyCode'] !== '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($data['outOrderId'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($data['amount'] != round($this->options['amount'] * 100)) {
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

        foreach ($this->encodeParams as $key) {
            $encodeData[$key] = $this->requestData[$key];
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['KEY'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeData = [];

        foreach ($this->trackingEncodeParams as $key) {
            $encodeData[$key] = $this->trackingRequestData[$key];
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['KEY'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}
